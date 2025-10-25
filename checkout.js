// Ensure the hidden city input is updated when a city is selected
const citySelect = document.getElementById('citymun_select');
const cityHidden = document.getElementById('city');
if (citySelect && cityHidden) {
    // On change
    citySelect.addEventListener('change', function () {
        const selectedOption = citySelect.options[citySelect.selectedIndex];
        cityHidden.value = selectedOption.text || '';
    });
    // On page load
    window.addEventListener('DOMContentLoaded', function () {
    if (citySelect.selectedIndex > 0) {
        const selectedOption = citySelect.options[citySelect.selectedIndex];
        cityHidden.value = selectedOption.text || '';
        // Also trigger barangay loading if city has value
        const cityCode = citySelect.value;
        if (cityCode) {
            // Try to get saved barangay code from a hidden input if available
            const savedBarangayCode = document.getElementById('barangay_code')?.value || '';
            // Assume loadBarangays is globally available (defined in checkout.php inline script)
            if (typeof loadBarangays === 'function') {
                loadBarangays(cityCode).then(() => {
                    if (savedBarangayCode && barangaySelect) {
                        barangaySelect.value = savedBarangayCode;
                        // Trigger change event in case any listeners
                        barangaySelect.dispatchEvent(new Event('change'));
                    }
                });
            }
        }
    }
});
}
// Ensure the hidden barangay input is updated when a barangay is selected
const barangaySelect = document.getElementById('barangay_select');
const barangayHidden = document.getElementById('barangay_name');
if (barangaySelect && barangayHidden) {
    barangaySelect.addEventListener('change', function () {
        const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
        barangayHidden.value = selectedOption.text || '';
    });
    window.addEventListener('DOMContentLoaded', function () {
        if (barangaySelect.selectedIndex > 0) {
            const selectedOption = barangaySelect.options[barangaySelect.selectedIndex];
            barangayHidden.value = selectedOption.text || '';
        }
    });
}
