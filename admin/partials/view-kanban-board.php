<div class="studiofy-kanban-wrapper">
	<?php foreach($columns as $s=>$l): ?>
	<div class="kanban-column" data-phase="<?php echo esc_attr($s); ?>">
		<div class="column-header"><h3><?php echo esc_html($l); ?></h3><span class="count"><?php echo count($projects[$s]); ?></span></div>
		<div class="kanban-dropzone">
			<?php foreach($projects[$s] as $p): $cl=get_post_meta($p->ID,'_studiofy_client_id',true); ?>
			<div class="kanban-card" data-id="<?php echo $p->ID; ?>">
				<strong><a href="<?php echo get_edit_post_link($p->ID); ?>"><?php echo esc_html($p->post_title); ?></a></strong>
				<div class="card-meta">Client ID: <?php echo esc_html($cl); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
