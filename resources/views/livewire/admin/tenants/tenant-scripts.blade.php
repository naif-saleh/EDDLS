<script>

    document.querySelectorAll('.open-modal').addEventListener('click', () => {
      setTimeout(() => {
        document.querySelectorAll('.hs-overlay').forEach((el) => HSOverlay.open(el));
      });
    });
  </script>


<script>
    Livewire.on('open-modal', () => {
        const modal = document.getElementById('hs-modal-recover-account');
        modal.classList.remove('hidden');
    });

    Livewire.on('close-modal', () => {
        const modal = document.getElementById('hs-modal-recover-account');
        modal.classList.add('hidden');
    });

 
</script>



