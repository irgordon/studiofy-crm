public function add_boxes(): void {
		// Project
		add_meta_box( 'studiofy_proj_cfg', 'Project Config', array( $this, 'render_project' ), 'studiofy_project', 'normal', 'high' );
		
		// Lead
		add_meta_box( 'studiofy_lead_info', 'Lead Details', array( $this, 'render_lead' ), 'studiofy_lead', 'normal', 'high' );
		
		// Session - Has Form Builder
		add_meta_box( 'studiofy_sess_form', 'Intake Questionnaire', array( $this, 'render_form_builder' ), 'studiofy_session', 'normal' );
		
		// Gallery
		add_meta_box( 'studiofy_gal_img', 'Gallery Images', array( $this, 'render_gallery' ), 'studiofy_gallery', 'normal' );
		
		// Contract
		add_meta_box( 'studiofy_cont_term', 'Payment & Terms', array( $this, 'render_contract_terms' ), 'studiofy_contract', 'normal' );
		add_meta_box( 'studiofy_cont_clause', 'Clauses', array( $this, 'render_contract_clauses' ), 'studiofy_contract', 'normal' );
		add_meta_box( 'studiofy_cont_stat', 'Status', array( $this, 'render_contract_status' ), 'studiofy_contract', 'side' );
	}
