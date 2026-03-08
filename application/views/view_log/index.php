<div class="alert alert-secondary" role="alert" style="margin-bottom: 0px !important;">
<div class="container">
	<?php if ($results) { ?>
		<div class="d-flex justify-content-between align-items-center">
			<p style="margin-bottom: 0px !important;"><?php echo lang('gen_hamradio_logbook'); ?>: <span class="badge text-bg-info"><?php echo $this->logbooks_model->find_name($this->session->userdata('active_station_logbook')); ?></span> <?php echo lang('general_word_location'); ?>: <span class="badge text-bg-info"><?php echo $this->stations->find_name(); ?></span></p>
			<?php if ($this->session->userdata('user_show_notes') == 1) { ?>
				<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stationDiaryModal">
					<i class="fas fa-book"></i> Station Diary
				</button>
			<?php } ?>
		</div>
	<?php } ?>
</div>
</div>

<div class="container logbook">

	<h2><?php echo lang('gen_hamradio_logbook'); ?></h2>
	<?php if($this->session->flashdata('notice')) { ?>
	<div class="alert alert-info" role="alert">
	  <?php echo $this->session->flashdata('notice'); ?>
	</div>
	<?php } ?>
</div>
	
<?php if($this->optionslib->get_option('logbook_map') != "false") { ?>
	<!-- Map -->
	<div id="map" class="map-leaflet" style="width: 100%; height: 350px"></div>
<?php } ?>

<div style="padding-top: 10px; margin-top: 0px;" class="container logbook">
	<?php $this->load->view('view_log/partial/log_ajax') ?>
</div>

<!-- Station Diary Modal -->
<?php if ($this->session->userdata('user_show_notes') == 1) { ?>
<div class="modal fade" id="stationDiaryModal" tabindex="-1" aria-labelledby="stationDiaryModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="stationDiaryModalLabel"><i class="fas fa-book"></i> Station Diary</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form id="stationDiaryForm" hx-post="<?php echo site_url('notes/quick_add'); ?>" hx-target="#diaryFormMessages" hx-encoding="multipart/form-data">
				<div class="modal-body">
					<div id="diaryFormMessages"></div>
					
					<div class="mb-3">
						<label for="diaryTitle" class="form-label fw-semibold">Title</label>
						<input type="text" class="form-control" id="diaryTitle" name="title" required placeholder="e.g. Good conditions on 20m">
					</div>

					<div class="mb-3">
						<label for="quickHiddenArea" class="form-label fw-semibold">Note</label>
						<div id="quickQuillArea" class="border rounded" style="height: 200px;"></div>
						<textarea name="content" style="display:none" id="quickHiddenArea"></textarea>
					</div>

					<div class="mb-3 border rounded p-3 bg-light">
						<div class="fw-semibold mb-3"><i class="fas fa-globe"></i> Public Diary Options</div>
						<div class="form-check mb-2">
							<input class="form-check-input" type="checkbox" value="1" id="quickIsPublic" name="is_public">
							<label class="form-check-label" for="quickIsPublic">
								<span class="badge bg-success me-1">Public</span> Make this entry visible on Station Diary
							</label>
						</div>
						<div class="form-check mb-3">
							<input class="form-check-input" type="checkbox" value="1" id="quickIncludeQso" name="include_qso_summary">
							<label class="form-check-label" for="quickIncludeQso">
								<i class="fas fa-list-ul me-1"></i> Include QSO summary for today
							</label>
						</div>
						<div class="mb-0 ms-4" id="quickLogbookSelector" style="display:none;">
							<label for="quickLogbookSelect" class="form-label small mb-1">Select logbook <span class="text-danger">*</span></label>
							<select name="logbook_id" class="form-select form-select-sm" id="quickLogbookSelect" required>
								<option value="">-- Choose a logbook --</option>
								<?php 
								$this->load->model('logbooks_model');
								$user_logbooks = $this->logbooks_model->show_all();
								if ($user_logbooks->num_rows() > 0) {
									foreach ($user_logbooks->result() as $logbook) { ?>
										<option value="<?php echo $logbook->logbook_id; ?>"><?php echo htmlspecialchars($logbook->logbook_name, ENT_QUOTES); ?></option>
									<?php }
								} ?>
							</select>
							<small class="form-text text-muted">QSO summary will be filtered to this logbook</small>
						</div>
					</div>

					<div class="mb-0">
						<label for="quickDiaryImages" class="form-label fw-semibold">
							<i class="fas fa-image me-1"></i> Images <span class="badge bg-secondary">Optional</span>
						</label>
						<input type="file" class="form-control" id="quickDiaryImages" name="diary_images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
						<small class="form-text text-muted">Maximum 2 MB per image</small>
					</div>

					<input type="hidden" name="category" value="Station Diary">
				</div>
				<div class="modal-footer bg-light">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
						<i class="fas fa-times me-1"></i> Close
					</button>
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-save me-1"></i> Save Note
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var quickIncludeQso = document.getElementById('quickIncludeQso');
	var quickLogbookSelector = document.getElementById('quickLogbookSelector');
	var quickLogbookSelect = document.getElementById('quickLogbookSelect');
	
	if (quickIncludeQso && quickLogbookSelector && quickLogbookSelect) {
		quickIncludeQso.addEventListener('change', function() {
			if (quickIncludeQso.checked) {
				quickLogbookSelector.style.display = 'block';
				quickLogbookSelect.setAttribute('required', 'required');
				// Fade in effect
				quickLogbookSelector.style.opacity = '0';
				setTimeout(function() {
					quickLogbookSelector.style.transition = 'opacity 0.3s';
					quickLogbookSelector.style.opacity = '1';
				}, 10);
			} else {
				quickLogbookSelect.removeAttribute('required');
				quickLogbookSelector.style.opacity = '0';
				setTimeout(function() {
					quickLogbookSelector.style.display = 'none';
				}, 300);
			}
		});
	}
	
	// Initialize Quill editor for Station Diary modal
	if (typeof Quill !== 'undefined') {
		var quickQuill = new Quill('#quickQuillArea', {
			placeholder: 'Compose an epic...',
			theme: 'snow'
		});
		
		// Copy Quill content to hidden textarea on form submit
		var stationDiaryForm = document.getElementById('stationDiaryForm');
		if (stationDiaryForm) {
			stationDiaryForm.addEventListener('submit', function(e) {
				var quillText = quickQuill.getText().trim();
				
				// Validate that Quill has content
				if (quillText.length === 0) {
					e.preventDefault();
					e.stopPropagation();
					
					// Show error message in the form
					var messagesDiv = document.getElementById('diaryFormMessages');
					if (messagesDiv) {
						messagesDiv.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
							'<i class="fas fa-exclamation-triangle me-2"></i>Please enter some content for your note.' +
							'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
							'</div>';
					}
					return false;
				}
				
				// Copy Quill HTML to hidden textarea
				document.getElementById('quickHiddenArea').value = quickQuill.root.innerHTML;
			});
		}
		
		// Reset Quill content when modal is hidden
		var stationDiaryModal = document.getElementById('stationDiaryModal');
		if (stationDiaryModal) {
			stationDiaryModal.addEventListener('hidden.bs.modal', function() {
				quickQuill.setText('');
				document.getElementById('quickHiddenArea').value = '';
				// Clear any error messages
				var messagesDiv = document.getElementById('diaryFormMessages');
				if (messagesDiv) {
					messagesDiv.innerHTML = '';
				}
				// Reset checkboxes and hide logbook selector
				if (quickIncludeQso) quickIncludeQso.checked = false;
				var quickIsPublic = document.getElementById('quickIsPublic');
				if (quickIsPublic) quickIsPublic.checked = false;
				if (quickLogbookSelector) {
					quickLogbookSelector.style.display = 'none';
					quickLogbookSelector.style.opacity = '1';
				}
			});
		}
	}
});
</script>
<?php } ?>
