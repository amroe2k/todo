
// SweetAlert fallback & test
$(function() {
	if (typeof Swal === 'undefined') {
		// Jika SweetAlert2 tidak terload, tampilkan alert biasa
		window.showSwal = function(opts) {
			alert(opts.title + (opts.text ? '\n' + opts.text : ''));
		};
	} else {
		window.showSwal = function(opts) {
			Swal.fire(opts);
		};
	}

	// Test: klik di mana saja untuk menampilkan SweetAlert demo
	$(document).on('dblclick', function() {
		showSwal({
			title: 'SweetAlert Berfungsi!',
			text: 'Jika popup ini muncul, SweetAlert2 sudah aktif.',
			icon: 'success',
			confirmButtonText: 'OK',
		});
	});
});
