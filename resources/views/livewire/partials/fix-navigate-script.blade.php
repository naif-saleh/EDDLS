<script>
 document.addEventListener('livewire:navigated', () => {
// Reinitialize Preline UI components after navigation
if (typeof HSStaticMethods !== 'undefined') {
    HSStaticMethods.autoInit();
}
});

// Alternative approach if you're using Preline directly
document.addEventListener('livewire:navigated', () => {
// Reinitialize dropdowns
if (typeof HSDropdown !== 'undefined') {
    document.querySelectorAll('.hs-dropdown').forEach(el => {
        new HSDropdown(el);
    });
}
});
</script>
