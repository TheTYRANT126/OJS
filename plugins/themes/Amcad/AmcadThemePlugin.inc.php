<?php
import('lib.pkp.classes.plugins.ThemePlugin');

class AmcadThemePlugin extends ThemePlugin {

	/**
	 * Inicializa el tema
	 */
	public function init() {
		// Define que este tema hereda del Default Theme
		$this->setParent('defaultthemeplugin');
		
		// Aquí cargaremos tus estilos en el futuro
		// $this->addStyle('mis-estilos', 'styles/index.less');
	}

	/**
	 * Nombre que verás en el panel de admin
	 */
	function getDisplayName() {
		return 'Tema Amcad';
	}

	/**
	 * Descripción
	 */
	function getDescription() {
		return 'Tema personalizado para la asociación Amcad.';
	}
}
?>