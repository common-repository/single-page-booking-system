
function toggleAcordeon(element) {
  const contenido = element.nextElementSibling;
  const todosContenidos = document.querySelectorAll('.acordeon .contenido');

  todosContenidos.forEach((item) => {
    if (item !== contenido && item.classList.contains('mostrar')) {
      item.classList.remove('mostrar');
    }
  });

  contenido.classList.toggle('mostrar');
}
