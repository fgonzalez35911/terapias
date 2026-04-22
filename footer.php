    <?php if(strpos($_SERVER['SCRIPT_NAME'], 'login.php') === false): ?>
    </div> <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Sistema SGR - Federico Marcelo Gonzalez</p>
    </footer>
    <?php endif; ?>

    <script>
        // Funciones Globales para los botones que aún no están listos
        function proximamente(modulo) {
            Swal.fire({
                title: 'Módulo en desarrollo',
                text: 'Pronto podrás acceder a: ' + modulo,
                icon: 'info',
                confirmButtonColor: '#3498db'
            });
        }

        // Service Worker para la PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/terapias/sw.js')
                    .then(reg => console.log('SW PWA registrado correctamente'))
                    .catch(err => console.log('Error en SW', err));
            });
        }
    </script>
</body>
</html>
