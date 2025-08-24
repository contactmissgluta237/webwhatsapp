<!-- BEGIN: Vendor JS-->
<script src="{{ asset('modern/vendors/js/vendors.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset('modern/js/core/app-menu.min.js') }}"></script>
<script src="{{ asset('modern/js/core/app.min.js') }}"></script>
<!-- END: Theme JS-->


<!-- Bootstrap js-->
<script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>

<!-- BEGIN: SweetAlert2 JS-->
<script src="{{ asset('assets/vendors/js/extensions/sweetalert2.all.min.js') }}"></script>
<!-- END: SweetAlert2 JS-->

<!-- BEGIN: Toastr JS-->
<script src="{{ asset('assets/vendors/js/extensions/toastr.min.js') }}"></script>
<!-- END: Toastr JS-->

<!-- Global Flash Messages and Utilities -->
<script>
$(document).ready(function() {
    // Affichage automatique des messages flash avec toastr
    @if(session('success'))
        toastr.success('{{ session('success') }}');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}');
    @endif
    @if(session('warning'))
        toastr.warning('{{ session('warning') }}');
    @endif
    @if(session('info'))
        toastr.info('{{ session('info') }}');
    @endif

    // Initialize tooltips globalement
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers globalement
    $('[data-toggle="popover"]').popover();
});
</script>