document.addEventListener('DOMContentLoaded', () => {
   init();
});

function init() {
  if (window.prestashop?.component?.initComponents) {
    try {
      window.prestashop.component.initComponents(['TinyMCEEditor']);
      console.log('TinyMCEEditor initialized successfully');
    } catch (error) {
      console.error('Error initializing TinyMCEEditor:', error);
    }
  } else {
    setTimeout(init, 50);
  }
}