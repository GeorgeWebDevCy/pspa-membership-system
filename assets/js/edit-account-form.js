jQuery(function($){
    var $form = $('.woocommerce-EditAccountForm.edit-account');
    if (!$form.length) {
        return;
    }
    // Hide name fields and current password field
    $form.find('#account_first_name, #account_last_name, #account_display_name, #password_current').closest('p').hide();
    // Add dashboard class for consistent styling
    $form.addClass('pspa-dashboard');
    // Update submit button text and ensure styling
    var $button = $form.find('button[name="save_account_details"]');
    $button.text('Αποθήκευση αλλαγών εισόδου');
    $button.val('Αποθήκευση αλλαγών εισόδου');
    $button.addClass('woocommerce-Button button');
});
