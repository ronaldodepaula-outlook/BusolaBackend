<?php
require_once 'classe/Config.php';
require_once 'classe/ApiClient.php';
require_once 'classe/Auth.php';
require_once 'classe/verURL.php';

Auth::exigirAuth();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<?php include 'componentes/head.php' ?>

<body>

  <!-- ======= Header ======= -->
  <?php include 'componentes/header.php' ?>
  <!-- End Header -->

  <!-- ======= Sidebar ======= -->
  <?php include 'componentes/sidebar.php' ?>
  <!-- End Sidebar-->

  <main id="main" class="main">
    <?php
      $red = new verURL();
      $red->trocarURL($_GET['paginas'] ?? '');
    ?>
  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; <?php echo date('Y'); ?> <strong><span>busola</span></strong> &mdash; Gestão Inteligente de Riscos
    </div>
    <div class="credits">
      Desenvolvido por <a href="#">rdpsolutions</a>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.min.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>

  <!-- busola CRUD utilities -->
  <script src="assets/js/busola-crud.js"></script>
  <script>
  const API_TOKEN = <?php echo json_encode(Auth::getToken()); ?>;
  const API_BASE = <?php echo json_encode(API_BASE_URL); ?>;
  </script>

</body>

</html>
