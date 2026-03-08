<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars($page_title ?? 'Station Diary', ENT_QUOTES); ?></title>
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/default/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/fontawesome/css/all.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;0,900;1,400&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
	<style>
		body {
			background: #efefef;
			font-family: 'Lora', Georgia, serif;
		}

		.diary-shell {
			max-width: 1080px;
			margin: 2.5rem auto;
			background: #fff;
			border: 1px solid #e5e5e5;
			box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
		}

		.diary-inner {
			max-width: 920px;
			margin: 0 auto;
			padding: 1rem 1.5rem 2rem;
		}

		.diary-rule {
			border: 0;
			border-top: 1px solid #e3e6ea;
			opacity: 1;
			margin: 0.75rem 0;
		}

		.diary-top-nav {
			text-align: right;
			font-size: 0.9rem;
		}

		.diary-top-nav a {
			color: #6c757d;
			text-decoration: none;
			margin-left: 1.25rem;
			font-weight: 600;
		}

		.diary-top-nav a:hover {
			text-decoration: underline;
		}

		.diary-title {
			font-family: 'Merriweather', Georgia, serif;
			font-size: 2.6rem;
			font-weight: 900;
			text-align: center;
			color: #2f3a56;
			line-height: 1.2;
			margin: 0.25rem 0;
		}

		.diary-subtitle {
			text-align: center;
			font-style: italic;
			color: #7a8294;
			font-size: 1.15rem;
		}

		.diary-entry {
			page-break-inside: avoid;
			padding: 0.25rem 0 1rem;
		}

		.diary-entry-title {
			font-family: 'Merriweather', Georgia, serif;
			font-size: 2rem;
			font-weight: 700;
			color: #2f3a56;
			margin-bottom: 0.3rem;
		}

		.diary-entry-date {
			font-size: 1.15rem;
			font-weight: 600;
			color: #5c6885;
			margin-bottom: 1rem;
		}

		.note-content {
			color: #44506b;
			font-size: 1.12rem;
			line-height: 1.8;
		}

		.note-content p:last-child { margin-bottom: 0; }

		.diary-entry img {
			max-width: 100%;
			height: auto;
			border: 1px solid #d4dae4;
		}

		.diary-qso-box {
			background: #f2f7fd;
			border: 1px solid #cfdff0;
			border-radius: 0.25rem;
			padding: 1rem;
		}

		.diary-qso-title {
			font-size: 1.45rem;
			font-weight: 700;
			color: #2f4f73;
		font-family: 'Merriweather', Georgia, serif;
			font-size: 0.9rem;
			margin-bottom: 0;
		}

		.diary-qso-box .table th {
			background: #e9f2fa;
			color: #2f4f73;
			font-weight: 600;
			border-bottom: 2px solid #cfdff0;
		}

		.diary-qso-box .table td {
			vertical-align: middle;
		}

		.diary-footer {
			text-align: center;
			font-style: italic;
			color: #8b92a1;
			margin-top: 1.5rem;
		}

		.diary-footer a {
			color: #5c6885;
			font-weight: 600;
			text-decoration: none;
		}

		.diary-footer a:hover {
			text-decoration: underline;
		}

		@media (max-width: 768px) {
			.diary-shell {
				margin: 0;
				box-shadow: none;
				border-left: 0;
				border-right: 0;
			}

			.diary-inner {
				padding: 0.75rem 1rem 1.5rem;
			}

			.diary-title {
				font-size: 2rem;
			}

			.diary-entry-title {
				font-size: 1.55rem;
			}

			.diary-top-nav {
				text-align: center;
			}

			.diary-top-nav a {
				margin: 0 0.6rem;
			}
		}

		@media print {
			.no-print { display: none !important; }
			body { background: #fff !important; }
			.diary-shell {
				box-shadow: none !important;
				border: 0 !important;
				margin: 0 !important;
			}
			.diary-qso-box details {
				display: block !important;
			}
			.diary-qso-box details summary {
				display: none !important;
			}
			.diary-qso-box details .table-responsive {
				display: block !important;
			}
		}

		.qso-summary-container {
			margin-top: 0.5rem;
		}
	</style>
</head>
<body>
	<div class="diary-shell">
		<div class="diary-inner">
			<div class="diary-top-nav no-print pt-2">
				<a href="<?php echo site_url('station-diary/' . rawurlencode($callsign)); ?>">Home</a>
				<a href="<?php echo htmlspecialchars($rss_url, ENT_QUOTES); ?>">RSS</a>
				<a href="#" onclick="window.print(); return false;">Print</a>
			</div>

			<hr class="diary-rule">

			<h1 class="diary-title"><?php echo htmlspecialchars($callsign, ENT_QUOTES); ?>'s Station Diary</h1>
			<div class="diary-subtitle">Notes from my ham radio adventures</div>

			<hr class="diary-rule mb-4">

			<?php if (!empty($entries)) { ?>
				<?php foreach ($entries as $entry) { ?>
					<article class="diary-entry">
						<h2 class="diary-entry-title"><?php echo htmlspecialchars($entry->title, ENT_QUOTES); ?></h2>
						<hr class="diary-rule mt-0">
						<div class="diary-entry-date"><?php echo date('F j, Y', strtotime($entry->created_at)); ?></div>

					<div class="note-content mb-4"><?php echo preg_replace('/<p><br\s*\/?><\/p>/i', '', $entry->note); ?></div>
								
								<!-- QSO Summary UPDATED VERSION 2.0 -->
								<?php if ((int)$entry->include_qso_summary === 1 && !empty($entry->qso_summary) && (int)($entry->qso_summary['total_qsos'] ?? 0) > 0) { ?>
									<hr class="diary-rule mt-2">
									<div class="bg-light border rounded p-3 mb-3">
										<div class="row g-3">
											<div class="col-6 col-sm-3">
												<div class="text-center">
													<div class="small text-muted">Total QSOs</div>
													<div class="h5 mb-0 fw-bold"><?php echo (int)$entry->qso_summary['total_qsos']; ?></div>
												</div>
											</div>
											<div class="col-6 col-sm-3">
												<div class="text-center">
													<div class="small text-muted">DXCC</div>
													<div class="h5 mb-0 fw-bold"><?php echo (int)$entry->qso_summary['dxcc_worked']; ?></div>
												</div>
											</div>
											<div class="col-6 col-sm-3">
												<div class="text-center">
													<div class="small text-muted">Bands</div>
													<div class="small"><span class="badge bg-primary"><?php echo !empty($entry->qso_summary['bands']) ? htmlspecialchars(implode(', ', $entry->qso_summary['bands']), ENT_QUOTES) : '-'; ?></span></div>
												</div>
											</div>
											<div class="col-6 col-sm-3">
												<div class="text-center">
													<div class="small text-muted">Modes</div>
													<div class="small"><span class="badge bg-secondary"><?php echo !empty($entry->qso_summary['modes']) ? htmlspecialchars(implode(', ', $entry->qso_summary['modes']), ENT_QUOTES) : '-'; ?></span></div>
												</div>
											</div>
										</div>
									</div>

									<div class="qso-summary-container">
										<?php if (!empty($entry->qso_summary['highlight_dx'])) { ?>
											<div class="alert alert-info mb-3">
												<div class="small mb-1"><strong>Highlight DX:</strong></div>
												<div class="d-flex align-items-center gap-2">
													<span class="h6 mb-0 highlight-dx-call"><?php echo htmlspecialchars($entry->qso_summary['highlight_dx']->COL_CALL, ENT_QUOTES); ?></span>
													<span class="badge bg-dark highlight-dx-country"><?php echo htmlspecialchars($entry->qso_summary['highlight_dx']->COL_COUNTRY ?? '-', ENT_QUOTES); ?></span>
													<span class="text-muted ms-auto"><span class="highlight-dx-distance"><?php echo (int)$entry->qso_summary['highlight_dx']->COL_DISTANCE; ?></span> km</span>
												</div>
											</div>
										<?php } ?>
										
										<?php if (!empty($entry->qso_list)) { ?>
											<details class="mt-2">
											<summary class="fw-bold" style="cursor: pointer; color: #2f4f73;">View QSO List (<span class="qso-count"><?php echo count($entry->qso_list); ?></span> contacts)</summary>
											<div class="table-responsive mt-2">
												<table class="table table-sm table-striped">
													<thead>
														<tr>
															<th>Time</th>
															<th>Call</th>
															<th>Band</th>
															<th>Mode</th>
															<th>Country</th>
															<th>Grid</th>
														</tr>
													</thead>
													<tbody class="qso-table-body">
														<?php foreach ($entry->qso_list as $qso) { ?>
															<tr>
																<td><?php echo date('H:i', strtotime($qso->COL_TIME_ON)); ?></td>
																<td><strong><?php echo htmlspecialchars($qso->COL_CALL, ENT_QUOTES); ?></strong></td>
																<td><?php echo htmlspecialchars($qso->COL_BAND ?? '-', ENT_QUOTES); ?></td>
																<td><?php echo htmlspecialchars(!empty($qso->COL_SUBMODE) ? $qso->COL_SUBMODE : $qso->COL_MODE, ENT_QUOTES); ?></td>
																<td><?php echo htmlspecialchars($qso->COL_COUNTRY ?? '-', ENT_QUOTES); ?></td>
																<td><?php echo htmlspecialchars($qso->COL_GRIDSQUARE ?? '-', ENT_QUOTES); ?></td>
															</tr>
														<?php } ?>
													</tbody>
												</table>
											</div>
										</details>
									<?php } ?>
								</div>
								<?php } ?>

						<?php if (!empty($entry->images)) { ?>
							<div class="row g-2 mb-3">
								<?php foreach ($entry->images as $image) { ?>
									<div class="col-md-6">
										<img src="<?php echo base_url() . ltrim($image->filename, '/'); ?>" alt="Diary image" class="img-fluid">
										<?php if (!empty($image->caption)) { ?>
											<div class="small text-muted mt-1"><?php echo htmlspecialchars($image->caption, ENT_QUOTES); ?></div>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

				<?php if (((int)$entry->include_qso_summary === 1 && !empty($entry->qso_summary) && (int)($entry->qso_summary['total_qsos'] ?? 0) > 0) || !empty($entry->images)) { ?>
					<hr class="diary-rule mt-4">
				<?php } ?>
				<?php if (!empty($pagination_links)) { ?>
				<nav aria-label="Station diary pages" class="d-flex justify-content-center no-print mt-3">
						<?php echo $pagination_links; ?>
					</nav>
				<?php } ?>
			<?php } else { ?>
				<div class="text-center py-5">
					<p class="mb-0">No public station diary entries found.</p>
				</div>
			<?php } ?>

			<div class="diary-footer">
				Powered by <a href="https://github.com/magicbug/Cloudlog" target="_blank" rel="noopener noreferrer">Cloudlog</a>
			</div>
		</div>
	</div>

	<script>
		document.querySelectorAll('.qso-date-filter, .qso-satellite-filter').forEach(el => {
			el.addEventListener('change', function() {
				const entryId = this.dataset.entryId;
				const startDate = document.querySelector(`input[data-entry-id="${entryId}"][data-filter-type="start"]`).value;
				const endDate = document.querySelector(`input[data-entry-id="${entryId}"][data-filter-type="end"]`).value;
				const satOnly = document.querySelector(`#sat-filter-${entryId}`).checked;
				
				const formData = new URLSearchParams();
				formData.append('callsign', '<?php echo htmlspecialchars($callsign ?? '', ENT_QUOTES); ?>');
				formData.append('entry_id', entryId);
				formData.append('start_date', startDate);
				formData.append('end_date', endDate);
				formData.append('sat_only', satOnly ? '1' : '0');
				
				fetch('<?php echo base_url(); ?>index.php/stationdiary/get_filtered_qsos', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					body: formData
				})
				.then(r => r.json())
				.then(data => {
					if (data.success) {
						const container = document.querySelector(`#qso-summary-${entryId}`);
						
						// Update highlight DX if present
						const dxCall = container.querySelector('.highlight-dx-call');
						if (dxCall && data.highlight_dx) {
							dxCall.textContent = data.highlight_dx.COL_CALL || '-';
							const dxCountry = container.querySelector('.highlight-dx-country');
							const dxDistance = container.querySelector('.highlight-dx-distance');
							if (dxCountry) dxCountry.textContent = data.highlight_dx.COL_COUNTRY || '-';
							if (dxDistance) dxDistance.textContent = parseInt(data.highlight_dx.COL_DISTANCE || 0);
						}
						
						// Update QSO count and table
						const countEl = container.querySelector('.qso-count');
						if (countEl) countEl.textContent = data.qso_list.length;
						
						const tbody = container.querySelector('.qso-table-body');
						if (tbody && data.qso_list.length > 0) {
							tbody.innerHTML = data.qso_list.map(qso => {
								const timeStr = new Date(qso.COL_TIME_ON).toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit'});
								const ent = (text) => (text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
								return `<tr><td>${timeStr}</td><td><strong>${ent(qso.COL_CALL)}</strong></td><td>${ent(qso.COL_BAND || '-')}</td><td>${ent((qso.COL_SUBMODE || qso.COL_MODE) || '-')}</td><td>${ent(qso.COL_COUNTRY || '-')}</td><td>${ent(qso.COL_GRIDSQUARE || '-')}</td></tr>`;
							}).join('');
						} else if (tbody) {
							tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No QSOs match the selected filters</td></tr>';
						}
					}
				})
				.catch(e => console.error('Filter error:', e));
			});
		});
		
		document.querySelectorAll('.qso-filter-reset').forEach(btn => {
			btn.addEventListener('click', function() {
				const entryId = this.dataset.entryId;
				const satCheckbox = document.querySelector(`#sat-filter-${entryId}`);
				if (satCheckbox) satCheckbox.checked = false;
				
				const startInput = document.querySelector(`input[data-entry-id="${entryId}"][data-filter-type="start"]`);
				if (startInput) {
					const today = startInput.value;
					const endInput = document.querySelector(`input[data-entry-id="${entryId}"][data-filter-type="end"]`);
					if (endInput) endInput.value = today;
					startInput.dispatchEvent(new Event('change', {bubbles: true}));
				}
			});
		});
	</script>
