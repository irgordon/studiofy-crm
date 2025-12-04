public function enqueue_scripts( string $hook ): void {
		global $post;
		
		// Media Uploader for Gallery
		if ( $post && 'studiofy_gallery' === $post->post_type ) {
			wp_enqueue_media();
		}

		// Only load on Studiofy pages
		if ( false === strpos( $hook, 'studiofy' ) && false === strpos( $hook, 'post.php' ) && false === strpos( $hook, 'post-new.php' ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-admin', STUDIOFY_URL . 'admin/js/studiofy-admin.js', array( 'jquery' ), $this->version, true );
		
		// FIX: Enable Form Builder JS on Project, Lead, AND Session
		// This enables the "Add Field" button functionality.
		if ( $post && in_array( $post->post_type, array( 'studiofy_session', 'studiofy_project', 'studiofy_lead' ) ) ) {
			wp_enqueue_script( 
				$this->plugin_name . '-builder', 
				STUDIOFY_URL . 'admin/js/studiofy-form-builder.js', 
				array( 'jquery', 'jquery-ui-sortable' ), 
				$this->version, 
				true 
			);
		}
	}
