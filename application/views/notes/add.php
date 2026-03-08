
<div class="container notes">
	<div class="row">
		<div class="col-12 col-xl-10">
			<div class="card shadow-sm">
			  <div class="card-header">
			    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
			    	<div class="d-flex flex-column">
			    		<a class="small text-decoration-none" href="<?php echo site_url('notes'); ?>">&larr; <?php echo lang('notes_menu_notes'); ?></a>
			    		<h2 class="card-title mb-0"><?php echo lang('notes_create_note'); ?></h2>
			    	</div>
			    	<ul class="nav nav-tabs card-header-tabs ms-auto">
				    <li class="nav-item">
				    	<a class="nav-link" href="<?php echo site_url('notes'); ?>"><?php echo lang('notes_menu_notes'); ?></a>
				    </li>
				    <li class="nav-item">
				    	<a class="nav-link active" href="<?php echo site_url('notes/add'); ?>"><?php echo lang('notes_create_note'); ?></a>
				    </li>
				</ul>
			    </div>
			  </div>

			  <div class="card-body">

  	<?php if (!empty(validation_errors())): ?>
    <div class="alert alert-danger">
        <a class="btn-close" data-bs-dismiss="alert" title="close">x</a>
        <ul><?php echo (validation_errors('<li>', '</li>')); ?></ul>
    </div>
	<?php endif; ?>

	<?php
	$defaultCategories = array('General', 'Antennas', 'Satellites');
	$existingCategories = isset($categories) && is_array($categories) ? $categories : array();
	$categoryOptions = array_values(array_unique(array_merge($defaultCategories, $existingCategories)));
	$selectedCategory = set_value('category', 'General');
	?>

	<form method="post" action="<?php echo site_url('notes/add'); ?>" name="notes_add" id="notes_add" enctype="multipart/form-data">

	<div class="mb-3">
		<label for="inputTitle" class="form-label"><?php echo lang('notes_input_title'); ?></label>
		<input type="text" name="title" class="form-control" id="inputTitle" value="<?php echo set_value('title'); ?>" placeholder="e.g. Field day setup" autocomplete="off" required>
	</div>

	<div class="mb-3">
	   <label for="catSelect" class="form-label"><?php echo lang('notes_input_category'); ?></label>
	   <select name="category" class="form-select" id="catSelect">
	   	<?php foreach ($categoryOptions as $catOption) { ?>
	   	<option value="<?php echo $catOption; ?>" <?php echo ($selectedCategory === $catOption) ? 'selected' : ''; ?>><?php echo $catOption; ?></option>
	   	<?php } ?>
	   </select>
	   <small class="text-muted d-block mt-1">Select an existing category or type a new one below.</small>
	</div>

	<div class="mb-3">
		<label for="newCategoryInput" class="form-label">New category (optional)</label>
		<input type="text" name="new_category" class="form-control" id="newCategoryInput" value="<?php echo set_value('new_category'); ?>" placeholder="e.g. Portable Ops" autocomplete="off">
		<small class="text-muted">If filled, this will be used instead of the selected category.</small>
	</div>

	<div class="mb-3 border rounded p-3 bg-light">
		<div class="fw-semibold mb-2">Station Diary Visibility</div>
		<?php if (isset($public_station_diary_enabled) && !$public_station_diary_enabled) { ?>
			<div class="alert alert-warning mb-2">Public Station Diary is globally disabled. Entries will remain private.</div>
		<?php } ?>
		<div class="form-check mb-2">
			<input class="form-check-input" type="checkbox" value="1" id="isPublicEntry" name="is_public" <?php echo set_value('is_public') ? 'checked' : ''; ?> <?php echo (isset($public_station_diary_enabled) && !$public_station_diary_enabled) ? 'disabled' : ''; ?>>
			<label class="form-check-label" for="isPublicEntry">🌍 Public entry (only applies to category "Station Diary")</label>
		</div>
		<div class="form-check mb-2">
			<input class="form-check-input" type="checkbox" value="1" id="includeQsoSummary" name="include_qso_summary" <?php echo set_value('include_qso_summary') ? 'checked' : ''; ?> <?php echo (isset($public_station_diary_enabled) && !$public_station_diary_enabled) ? 'disabled' : ''; ?>>
			<label class="form-check-label" for="includeQsoSummary">Include QSO summary block on public page</label>
		</div>
		<div class="mb-0" id="logbookSelectorContainer" style="<?php echo set_value('include_qso_summary') ? '' : 'display:none;'; ?>">
			<label for="logbookSelect" class="form-label small text-muted">Select Logbook <span class="text-danger">*</span></label>
			<select name="logbook_id" class="form-select form-select-sm" id="logbookSelect" required>
				<option value="">-- Choose a logbook --</option>
				<?php if (isset($user_logbooks) && $user_logbooks->num_rows() > 0) {
					foreach ($user_logbooks->result() as $logbook) { ?>
						<option value="<?php echo $logbook->logbook_id; ?>" <?php echo set_value('logbook_id') == $logbook->logbook_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($logbook->logbook_name, ENT_QUOTES); ?></option>
					<?php }
				} ?>
			</select>
			<small class="text-muted">QSO summary will be filtered to this logbook</small>
		</div>
	</div>

	<div class="mb-3">
		<label for="diaryImages" class="form-label">Diary images (optional)</label>
		<input type="file" class="form-control" id="diaryImages" name="diary_images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
		<small class="text-muted">Max 2 MB per image. Images are resized and compressed automatically.</small>
	</div>

	<div class="mb-3">
		<label for="hiddenArea" class="form-label"><?php echo lang('notes_input_notes_content'); ?></label>
		<div id="quillArea"></div>
		<textarea name="content" style="display:none" id="hiddenArea"></textarea>
	</div>

	<div class="d-flex flex-wrap gap-2">
		<button type="submit" value="Submit" class="btn btn-primary"><?php echo lang('notes_input_btn_save_note'); ?></button>
		<a href="<?php echo site_url('notes'); ?>" class="btn btn-outline-secondary"><?php echo lang('general_word_cancel') ?: 'Cancel'; ?></a>
	</div>
	</form>

	<div class="modal fade" id="confirmPublicModal" tabindex="-1" aria-labelledby="confirmPublicModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="confirmPublicModalLabel">Make this entry public?</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					This entry will become visible at your public station diary URL if category is <strong>Station Diary</strong>.
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="confirmPublicModalProceed">Make Public</button>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var isPublicEntry = document.getElementById('isPublicEntry');
			var includeQsoSummary = document.getElementById('includeQsoSummary');
			var logbookSelectorContainer = document.getElementById('logbookSelectorContainer');
			var logbookSelect = document.getElementById('logbookSelect');
			var confirmModalEl = document.getElementById('confirmPublicModal');
			var confirmModalProceed = document.getElementById('confirmPublicModalProceed');
			
			// Toggle logbook selector visibility and required attribute
			if (includeQsoSummary && logbookSelectorContainer && logbookSelect) {
				// Set initial state based on checkbox (for validation errors)
				if (includeQsoSummary.checked) {
					logbookSelect.setAttribute('required', 'required');
				} else {
					logbookSelect.removeAttribute('required');
				}
				
				includeQsoSummary.addEventListener('change', function() {
					if (includeQsoSummary.checked) {
						logbookSelectorContainer.style.display = 'block';
						logbookSelect.setAttribute('required', 'required');
					} else {
						logbookSelectorContainer.style.display = 'none';
						logbookSelect.removeAttribute('required');
					}
				});
			}
			
			if (!isPublicEntry) {
				return;
			}

			if (!confirmModalEl || !confirmModalProceed || typeof bootstrap === 'undefined') {
				return;
			}

			var confirmModal = new bootstrap.Modal(confirmModalEl);
			var allowPublicChange = false;

			isPublicEntry.addEventListener('change', function() {
				if (isPublicEntry.checked && !allowPublicChange) {
					isPublicEntry.checked = false;
					confirmModal.show();
				}
			});

			confirmModalProceed.addEventListener('click', function() {
				allowPublicChange = true;
				isPublicEntry.checked = true;
				confirmModal.hide();
				allowPublicChange = false;
			});
		});
	</script>
	  </div>
			</div>
		</div>
	</div>

</div>
