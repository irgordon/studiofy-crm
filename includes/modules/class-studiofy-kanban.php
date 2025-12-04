<?php
declare( strict_types=1 );
class Studiofy_Kanban {
	public function init(): void { add_action( 'wp_ajax_studiofy_update_kanban_status', array( $this, 'ajax_update' ) ); }
	public function render_page(): void {
		$tab = $_GET['tab'] ?? 'board';
		echo '<div class="wrap"><h1>Production Board</h1><h2 class="nav-tab-wrapper"><a href="?page=studiofy-projects&view=kanban&tab=board" class="nav-tab '.($tab=='board'?'nav-tab-active':'').'">Board</a><a href="?page=studiofy-projects&view=kanban&tab=reports" class="nav-tab '.($tab=='reports'?'nav-tab-active':'').'">Reports</a></h2>';
		if($tab=='reports') require_once STUDIOFY_PATH.'admin/partials/view-kanban-reports.php';
		else $this->render_board();
		echo '</div>';
	}
	private function render_board(): void {
		$cols = ['pre_production'=>'Pre-Production','production'=>'Production','post_production'=>'Post-Production','completed'=>'Completed'];
		$posts = get_posts(['post_type'=>'studiofy_project','posts_per_page'=>-1]);
		$projects = ['pre_production'=>[],'production'=>[],'post_production'=>[],'completed'=>[]];
		foreach($posts as $p) {
			$ph = get_post_meta($p->ID,'_studiofy_phase',true)?:'pre_production';
			$key = strtolower(str_replace('-','_',$ph));
			if(isset($projects[$key])) $projects[$key][] = $p;
			else $projects['pre_production'][] = $p;
		}
		require_once STUDIOFY_PATH.'admin/partials/view-kanban-board.php';
	}
	public function ajax_update(): void {
		check_ajax_referer('studiofy_kanban_nonce','security');
		update_post_meta(intval($_POST['post_id']),'_studiofy_phase',sanitize_text_field($_POST['phase']));
		wp_send_json_success();
	}
}
