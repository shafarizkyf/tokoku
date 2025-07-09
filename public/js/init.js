const on = (element, type, selector, handler) => {
  element.addEventListener(type, (event) => {
    if (event.target.closest(selector)) {
      handler(event);
    }
  });
};
