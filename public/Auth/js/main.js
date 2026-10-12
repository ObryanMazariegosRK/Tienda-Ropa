(function($) {

	"use strict";

	// Botón del ojo para mostrar u ocultar la contraseña
	$(document).on('click', '.toggle-password', function() {
		const input = document.getElementById(this.dataset.target);
		if (!input) return;

		const mostrar = input.type === 'password';
		input.type = mostrar ? 'text' : 'password';
		this.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
		$(this).find('.fa').toggleClass('fa-eye', !mostrar).toggleClass('fa-eye-slash', mostrar);
	});

})(jQuery);
