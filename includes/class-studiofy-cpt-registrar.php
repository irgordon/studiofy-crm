<?php
declare( strict_types=1 );
class Studiofy_CPT_Registrar {
	public function init(): void { add_action( 'init', array( $this, 'register_post_types' ) ); }
	public function register_post_types(): void {
		$slug = 'studiofy-dashboard';
		$types = [
			'studiofy_project'  => ['Project','Projects','dashicons-portfolio'],
			'studiofy_lead'     => ['Lead','Leads','dashicons-filter'],
			'studiofy_invoice'  => ['Invoice','Invoices','dashicons-money-alt'],
			'studiofy_contract' => ['Contract','Contracts','dashicons-media-document'],
			'studiofy_session'  => ['Session','Sessions','dashicons-calendar-alt'],
			'studiofy_gallery'  => ['Gallery','Galleries','dashicons-images-alt2']
		];
		foreach ($types as $k => $d) {
			register_post_type($k, ['labels'=>['name'=>$d[1],'singular_name'=>$d[0],'add_new'=>'Add New '.$d[0]],'public'=>in_array($k,['studiofy_invoice','studiofy_contract','studiofy_gallery']),'show_ui'=>true,'show_in_menu'=>$slug,'supports'=>['title','editor','thumbnail'],'menu_icon'=>$d[2]]);
		}
	}
}
