<?php

namespace App\Infraestructure\Services;

use App\Domain\Abstractions\IMailSenderRepository;
use App\Domain\Abstractions\User\IEmailService;
use App\Domain\Entities\MailSender;
use App\Mail\MailSenderTestMail;
use App\Mail\PasswordRecoveryMail;
use App\Mail\VerificationCodeMail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class LaravelEmailService implements IEmailService{

    public function __construct(private IMailSenderRepository $mailSenderRepository) {}

    public function sendVerificationCode(string $email, string $userName, string $code): void{
        $this->enviar($email, new VerificationCodeMail($userName, $code));
    }

    public function sendPasswordRecoveryCode(string $email, string $name, string $code): void{
        $this->enviar($email, new PasswordRecoveryMail($name, $code));
    }

    public function sendTestEmail(MailSender $sender, string $toEmail): void{
        try {
            $this->mailerPara($sender)->to($toEmail)->send(new MailSenderTestMail($sender->getFromName(), $sender->getEmail()));
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException($this->explicarError($e, $sender));
        }
    }

    //Se envía con la cuenta que el admin dejó "en uso". Si no hay ninguna, o si esa
    //cuenta falla (contraseña revocada, etc.), se usa la del .env para que el cliente
    //no se quede sin su código.
    private function enviar(string $to, Mailable $mail): void{
        $remitente = $this->mailSenderRepository->findActive();

        if ($remitente) {
            try {
                $this->mailerPara($remitente)->to($to)->send($mail);
                return;
            } catch (TransportExceptionInterface $e) {
                report($e);
            }
        }

        Mail::to($to)->send($mail);
    }

    private function mailerPara(MailSender $sender): Mailer{
        $mailer = Mail::build([
            'transport' => 'smtp',
            'host' => $sender->getHost(),
            'port' => $sender->getPort(),
            'username' => $sender->getEmail(),
            'password' => $sender->getPassword(),
            'timeout' => 15,
        ]);
        //Gmail solo deja enviar como la misma cuenta que inicia sesión
        $mailer->alwaysFrom($sender->getEmail(), $sender->getFromName());
        return $mailer;
    }

    private function explicarError(TransportExceptionInterface $e, MailSender $sender): string{
        $detalle = $e->getMessage();

        if (str_contains($detalle, '535') || str_contains($detalle, '534') || stripos($detalle, 'authenticat') !== false) {
            return 'El servidor rechazó el correo o la contraseña. Si es Gmail, usa una "contraseña de aplicación" '
                 . '(16 letras, requiere la verificación en 2 pasos), no la contraseña normal de la cuenta.';
        }
        if (stripos($detalle, 'connection') !== false || stripos($detalle, 'timed out') !== false) {
            return "No se pudo conectar con {$sender->getHost()}:{$sender->getPort()}. Revisa el servidor y el puerto "
                 . '(Gmail: smtp.gmail.com, puerto 587).';
        }
        report($e);
        return 'No se pudo enviar el correo de prueba. Revisa los datos de la cuenta e inténtalo de nuevo.';
    }
}
