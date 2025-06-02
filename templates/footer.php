</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Закрытие модальных окон после успешных действий
    <?php if ($error && isset($_POST['register']) && strpos($error, 'success') !== false): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        registerModal.hide();
        document.getElementById('reset-view').addEventListener('click', () => {
            if (model) {
                controls.reset();
                camera.position.z = 5;
                camera.position.y = 2.5;
                camera.lookAt(0, 0, 0);
                controls.target.set(0, 0, 0);
                controls.update();
            }
        });
        var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    });
    <?php endif; ?>
</script>
</body>
</html>