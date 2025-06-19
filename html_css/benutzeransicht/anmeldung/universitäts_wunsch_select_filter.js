(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExclusiveSelection);
    } else {
        initExclusiveSelection();
    }
    
    function initExclusiveSelection() {
        // Find the select elements by looking for dhbwuni-select class or by field pattern
        var selects = document.querySelectorAll('select.dhbwuni-select');
        
        // If not found by class, try to find by name pattern
        if (selects.length === 0) {
            selects = document.querySelectorAll('select[name$="_selected"]');
            
            // Filter to only get the three university choice fields
            var filteredSelects = [];
            for (var i = 0; i < selects.length; i++) {
                var select = selects[i];
                var formGroup = select.closest('.col-md-8');
                if (formGroup) {
                    var parentRow = formGroup.closest('.row');
                    if (parentRow) {
                        var labelText = parentRow.querySelector('strong');
                        if (labelText && (
                            labelText.textContent.includes('Erstwunsch') ||
                            labelText.textContent.includes('Zweitwunsch') ||
                            labelText.textContent.includes('Drittwunsch')
                        )) {
                            filteredSelects.push(select);
                        }
                    }
                }
            }
            selects = filteredSelects;
        }
        
        console.log('Found ' + selects.length + ' university selection fields');
        
        if (selects.length < 2) {
            return; // No need for exclusive selection with less than 2 fields
        }
        
        // Store original options for each select
        var originalOptions = {};
        
        for (var i = 0; i < selects.length; i++) {
            var select = selects[i];
            var selectId = select.name || 'select_' + i;
            originalOptions[selectId] = [];
            
            for (var j = 0; j < select.options.length; j++) {
                var option = select.options[j];
                originalOptions[selectId].push({
                    value: option.value,
                    text: option.text,
                    html: option.outerHTML
                });
            }
        }
        
        // Function to update available options
        function updateOptions() {
            // Collect all selected values
            var selectedValues = {};
            
            for (var i = 0; i < selects.length; i++) {
                var select = selects[i];
                var value = select.value;
                if (value && value !== '' && value !== '0') {
                    selectedValues[value] = true;
                }
            }
            
            // Update each select
            for (var i = 0; i < selects.length; i++) {
                var select = selects[i];
                var currentValue = select.value;
                var selectId = select.name || 'select_' + i;
                
                // Save the current scroll position
                var scrollPosition = select.scrollTop;
                
                // Clear all options
                select.innerHTML = '';
                
                // Add back filtered options
                for (var j = 0; j < originalOptions[selectId].length; j++) {
                    var optionData = originalOptions[selectId][j];
                    
                    // Include option if:
                    // - It's empty (Choose option)
                    // - It's 0 (None option)
                    // - It's the currently selected value
                    // - It's not selected in any other field
                    if (optionData.value === '' || 
                        optionData.value === '0' || 
                        optionData.value === currentValue || 
                        !selectedValues[optionData.value]) {
                        
                        var newOption = document.createElement('option');
                        newOption.value = optionData.value;
                        newOption.text = optionData.text;
                        select.appendChild(newOption);
                    }
                }
                
                // Restore the selected value
                select.value = currentValue;
                
                // Restore scroll position
                select.scrollTop = scrollPosition;
            }
        }
        
        // Attach change event listeners
        for (var i = 0; i < selects.length; i++) {
            selects[i].addEventListener('change', updateOptions);
        }
        
        // Run initial update
        updateOptions();
        
        // Also update when form is shown/loaded (for cases where the form might be hidden initially)
        setTimeout(updateOptions, 100);
    }
})();