<div class="wrap">
    <h1><?php echo $contract ? 'Edit Contract' : 'New Contract'; ?></h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="studiofy_save_contract">
        <?php wp_nonce_field('save_contract', 'studiofy_nonce'); ?>
        <?php if ($contract) {
            echo '<input type="hidden" name="contract_id" value="' . $contract->id . '">';
            echo '<input type="hidden" name="linked_post_id" value="' . $contract->linked_post_id . '">';
        } ?>

        <div class="studiofy-panel">
            <label for="con_title">Contract Title *</label>
            <input type="text" name="title" id="con_title" required class="widefat" value="<?php echo $contract->title ?? ''; ?>" title="Contract Title">
            
            <div class="studiofy-form-row">
                <div class="studiofy-col">
                    <label for="con_customer">Client *</label>
                    <select name="customer_id" id="con_customer" required class="widefat" title="Select Client">
                        <option value="">Select client</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}' " . selected($contract->customer_id ?? 0, $c->id, false) . ">{$c->first_name} {$c->last_name}</option>"; ?>
                    </select>
                </div>
                <div class="studiofy-col">
                    <label for="con_project">Project (Optional)</label>
                    <select name="project_id" id="con_project" class="widefat" title="Select Project">
                        <option value="">Select project</option>
                        <?php foreach($projects as $p) echo "<option value='{$p->id}' " . selected($contract->project_id ?? 0, $p->id, false) . ">{$p->title}</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="studiofy-form-row">
                <div class="studiofy-col"><label for="con_start">Start Date *</label><input type="date" name="start_date" id="con_start" required class="widefat" value="<?php echo $contract->start_date ?? ''; ?>" title="Start Date"></div>
                <div class="studiofy-col"><label for="con_end">End Date *</label><input type="date" name="end_date" id="con_end" required class="widefat" value="<?php echo $contract->end_date ?? ''; ?>" title="End Date"></div>
                <div class="studiofy-col"><label for="con_amt">Value *</label><input type="number" step="0.01" name="amount" id="con_amt" required class="widefat" value="<?php echo $contract->amount ?? '0'; ?>" title="Amount"></div>
            </div>
            
            <div style="margin-top:10px;">
                <label for="con_status">Status</label>
                <select name="status" id="con_status" class="widefat" title="Status">
                    <option value="Draft" <?php selected($contract->status ?? '', 'Draft'); ?>>Draft</option>
                    <option value="Active" <?php selected($contract->status ?? '', 'Active'); ?>>Active</option>
                </select>
            </div>
        </div>

        <div class="studiofy-panel" style="margin-top:20px; text-align:center; padding: 40px;">
            <h3>Contract Layout & Terms</h3>
            
            <?php if (!empty($elementor_url)): ?>
                <p>Contract content is managed via Elementor.</p>
                <a href="<?php echo esc_url($elementor_url); ?>" class="button button-primary button-hero" target="_blank">
                    Edit with Elementor
                </a>
                <p class="description" style="margin-top:10px;">Clicking will open the visual editor in a new tab. Save changes there, then refresh this page.</p>
            <?php else: ?>
                <p>Save this contract first to enable the Elementor Visual Editor.</p>
                <?php submit_button('Save & Enable Editor'); ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($elementor_url)): ?>
            <p class="submit">
                <button type="submit" class="button button-primary">Update Contract Details</button>
            </p>
        <?php endif; ?>
    </form>
</div>
