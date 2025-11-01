# WP App Core - Modal Template System

**Version**: 1.0.0
**TODO**: TODO-1194
**Status**: ✅ COMPLETED

## Overview

Centralized modal template system for all wp-app-core based plugins. Provides a single, flexible modal template that supports multiple modal types (form, confirmation, info) with dynamic content loading and hook-based plugin integration.

## Features

- ✅ Single flexible template for all modal types
- ✅ Hook-based content injection
- ✅ AJAX content loading
- ✅ Dynamic button configuration
- ✅ ESC key to close
- ✅ Click overlay to close (configurable)
- ✅ Loading states
- ✅ Size options (small/medium/large)
- ✅ Auto-close for info modals
- ✅ Form validation display support
- ✅ Event system (opened/closed/submit)
- ✅ Accessibility (ARIA attributes)
- ✅ WordPress admin styling integration
- ✅ Responsive design

## File Structure

```
/wp-app-core/
  /src/
    /Views/
      /Modal/
        ModalTemplate.php           # Main modal template class
        README.md                   # This file
  /assets/
    /js/
      /modal/
        wpapp-modal-manager.js      # JavaScript API
    /css/
      /modal/
        wpapp-modal.css             # Modal styling
```

## Modal Types

### 1. Form Modal
**Use Case**: Create/Edit forms

**Default Buttons**:
- Cancel (left)
- Save/Submit (right, primary)

**Example**:
```javascript
wpAppModal.show({
    type: 'form',
    title: 'Add New Customer',
    bodyUrl: ajaxurl + '?action=get_customer_form&mode=create',
    size: 'medium',
    onSubmit: function(formData, $form) {
        // Handle form submission
    }
});
```

### 2. Confirmation Modal
**Use Case**: Delete confirmations, action confirmations

**Default Buttons**:
- Cancel (left)
- Confirm (right, primary)

**Example**:
```javascript
wpAppModal.confirm({
    title: 'Delete Customer?',
    message: 'Are you sure you want to delete this customer?',
    danger: true,
    onConfirm: function() {
        // Perform delete action
    }
});
```

### 3. Info Modal
**Use Case**: Success/Error/Warning messages

**Default Buttons**:
- OK (center, primary)

**Example**:
```javascript
wpAppModal.info({
    infoType: 'success',
    title: 'Success',
    message: 'Customer saved successfully!',
    autoClose: 3000
});
```

## JavaScript API

### Core Methods

#### `wpAppModal.show(config)`
Show modal with custom configuration.

**Parameters**:
```javascript
{
    type: 'form',              // 'form'|'confirmation'|'info'
    title: 'Modal Title',      // Modal title text
    body: '<p>HTML</p>',       // Direct HTML content (optional)
    bodyUrl: 'url',            // AJAX URL to load content (optional)
    size: 'medium',            // 'small'|'medium'|'large'
    buttons: {...},            // Button configuration (optional)
    preventClose: false,       // Prevent close on overlay/ESC
    autoClose: 0,              // Auto-close in ms (0 = disabled)
    onSubmit: function() {},   // Submit callback
    onClose: function() {},    // Close callback
    onConfirm: function() {}   // Confirm callback
}
```

#### `wpAppModal.hide()`
Hide and reset modal.

#### `wpAppModal.setContent(html)`
Update modal body content.

**Parameters**:
- `html` (string): HTML content to display

#### `wpAppModal.setTitle(title)`
Update modal title.

**Parameters**:
- `title` (string): Title text

#### `wpAppModal.setButtons(buttons)`
Update footer buttons.

**Parameters**:
```javascript
{
    cancel: {
        label: 'Cancel',
        class: 'button',
        action: 'cancel'
    },
    submit: {
        label: 'Save',
        class: 'button button-primary',
        type: 'submit',
        action: 'submit'
    }
}
```

#### `wpAppModal.loading(show)`
Show/hide loading state.

**Parameters**:
- `show` (boolean): true to show loading, false to hide

### Convenience Methods

#### `wpAppModal.confirm(config)`
Shortcut for confirmation modal.

**Parameters**:
```javascript
{
    title: 'Confirm Action',
    message: 'Are you sure?',
    danger: true,              // Apply danger styling
    confirmLabel: 'Confirm',   // Custom confirm button label
    onConfirm: function() {}
}
```

#### `wpAppModal.info(config)`
Shortcut for info modal.

**Parameters**:
```javascript
{
    infoType: 'success',       // 'success'|'error'|'warning'|'info'
    title: 'Information',
    message: 'Message text',
    autoClose: 3000            // Auto-close in ms
}
```

## Event System

### Available Events

#### `wpapp:modal-opened`
Fired when modal is shown.

**Handler**:
```javascript
$(document).on('wpapp:modal-opened', function(event, config) {
    console.log('Modal opened with config:', config);
});
```

#### `wpapp:modal-closed`
Fired when modal is hidden.

**Handler**:
```javascript
$(document).on('wpapp:modal-closed', function(event) {
    console.log('Modal closed');
});
```

#### `wpapp:modal-submit`
Fired when submit button clicked (form modals).

**Handler**:
```javascript
$(document).on('wpapp:modal-submit', function(event, formData) {
    console.log('Form submitted with data:', formData);
});
```

## Plugin Integration

### PHP: AJAX Form Content Handler

```php
// In your plugin controller
public function handle_get_customer_form() {
    // Verify nonce
    check_ajax_referer('customer_nonce', 'nonce');

    $mode = $_GET['mode'] ?? 'create';
    $customer_id = $_GET['id'] ?? 0;

    // Load appropriate form template
    if ($mode === 'edit' && $customer_id) {
        $customer = CustomerModel::get_by_id($customer_id);
        include 'forms/edit-customer-form.php';
    } else {
        include 'forms/create-customer-form.php';
    }

    wp_die();
}

// Register AJAX action
add_action('wp_ajax_get_customer_form', [$this, 'handle_get_customer_form']);
```

### PHP: Form Template

```php
<!-- forms/create-customer-form.php -->
<form id="customer-form" class="wpapp-modal-form">
    <input type="hidden" name="action" value="save_customer">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('customer_nonce'); ?>">

    <div class="wpapp-form-field">
        <label for="customer-name">
            Customer Name <span class="required">*</span>
        </label>
        <input type="text"
               id="customer-name"
               name="customer_name"
               required>
        <span class="description">Enter the full customer name</span>
    </div>

    <div class="wpapp-form-field">
        <label for="customer-email">Email</label>
        <input type="email"
               id="customer-email"
               name="customer_email">
    </div>

    <!-- More fields... -->
</form>
```

### JavaScript: Modal Triggers

```javascript
// In your plugin's JavaScript file

$(document).ready(function() {

    // Add New button
    $(document).on('click', '.customer-add-btn', function(e) {
        e.preventDefault();

        wpAppModal.show({
            type: 'form',
            title: 'Add New Customer',
            bodyUrl: ajaxurl + '?action=get_customer_form&mode=create',
            size: 'medium',
            buttons: {
                cancel: { label: 'Cancel' },
                submit: { label: 'Save Customer', primary: true }
            },
            onSubmit: function(formData, $form) {
                // Handle AJAX submission
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            wpAppModal.info({
                                infoType: 'success',
                                title: 'Success',
                                message: 'Customer saved successfully!',
                                autoClose: 3000
                            });

                            // Reload DataTable
                            window.wpCustomerTable.ajax.reload();
                        }
                    }
                });
            }
        });
    });

    // Delete button
    $(document).on('click', '.customer-delete-btn', function(e) {
        e.preventDefault();

        var customerId = $(this).data('customer-id');
        var customerName = $(this).data('customer-name');

        wpAppModal.confirm({
            title: 'Delete Customer?',
            message: 'Are you sure you want to delete <strong>' + customerName + '</strong>?',
            danger: true,
            onConfirm: function() {
                // Perform delete
                $.post(ajaxurl, {
                    action: 'delete_customer',
                    customer_id: customerId,
                    nonce: wpAppConfig.nonce
                }, function(response) {
                    if (response.success) {
                        wpAppModal.info({
                            infoType: 'success',
                            message: 'Customer deleted successfully',
                            autoClose: 3000
                        });
                    }
                });
            }
        });
    });
});
```

## Size Options

### Small (400px)
Best for: Confirmations, simple info messages

```javascript
wpAppModal.show({
    size: 'small',
    // ...
});
```

### Medium (600px) - Default
Best for: Standard forms, detailed messages

```javascript
wpAppModal.show({
    size: 'medium',
    // ...
});
```

### Large (800px)
Best for: Complex forms, multi-section content

```javascript
wpAppModal.show({
    size: 'large',
    // ...
});
```

## Button Configuration

### Default Buttons by Type

**Form**:
- Cancel (button)
- Save (button-primary)

**Confirmation**:
- Cancel (button)
- Confirm (button-primary)

**Info**:
- OK (button-primary)

### Custom Buttons

```javascript
wpAppModal.show({
    // ...
    buttons: {
        cancel: {
            id: 'my-cancel-btn',           // Optional ID
            label: 'Cancel',                // Button text
            class: 'button',                // CSS classes
            type: 'button',                 // button|submit
            action: 'cancel',               // Action identifier
            disabled: false,                // Disabled state
            data: {                         // Custom data attributes
                customValue: 'value'
            }
        },
        submit: {
            label: 'Save Changes',
            class: 'button button-primary',
            type: 'submit',
            action: 'submit'
        }
    }
});
```

### Danger Button (Delete actions)

```javascript
buttons: {
    cancel: { label: 'Cancel', class: 'button' },
    confirm: {
        label: 'Delete',
        class: 'button button-primary button-danger'
    }
}
```

## Form Styling

### CSS Classes Available

**Form Container**:
```html
<form class="wpapp-modal-form">
```

**Form Field**:
```html
<div class="wpapp-form-field">
    <label>Field Label <span class="required">*</span></label>
    <input type="text" name="field_name">
    <span class="description">Help text</span>
</div>
```

**Error State**:
```html
<div class="wpapp-form-field error">
    <label>Field Label</label>
    <input type="text" name="field_name">
    <span class="error-message">Error message</span>
</div>
```

## Accessibility

### ARIA Attributes

The modal template includes proper ARIA attributes:
- `role="dialog"`
- `aria-modal="true"`
- `aria-labelledby` (references title)
- `aria-hidden` (managed by JavaScript)

### Keyboard Support

- **ESC**: Close modal (unless `preventClose: true`)
- **TAB**: Focus trap within modal
- **ENTER**: Submit form (in form modals)

### Screen Reader Support

- Close button has `aria-label`
- Semantic HTML structure
- Proper heading hierarchy

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE11+ (with polyfills)

## Responsive Design

Modal automatically adjusts for:
- Desktop (full width up to max-width)
- Tablet (95% width)
- Mobile (95% width, stacked buttons)

## Best Practices

### 1. Always Use AJAX for Form Content

✅ **Good**:
```javascript
bodyUrl: ajaxurl + '?action=get_customer_form'
```

❌ **Avoid** (unless very simple):
```javascript
body: '<form>...</form>'  // Hard to maintain
```

### 2. Handle Form Submission via Callback

✅ **Good**:
```javascript
onSubmit: function(formData, $form) {
    $.ajax({
        url: ajaxurl,
        data: formData,
        success: function(response) {
            // Handle response
        }
    });
}
```

❌ **Avoid**:
```javascript
// Default form submission (causes page reload)
```

### 3. Use Appropriate Modal Types

- **Form**: Create/Edit operations
- **Confirmation**: Delete/Irreversible actions
- **Info**: Messages/Notifications

### 4. Provide User Feedback

Always show success/error messages after actions:
```javascript
wpAppModal.info({
    infoType: 'success',
    message: 'Action completed',
    autoClose: 3000
});
```

### 5. Reload Data After Changes

```javascript
if (response.success) {
    wpAppModal.hide();
    window.wpCustomerTable.ajax.reload();  // Refresh DataTable
}
```

## Troubleshooting

### Modal Not Appearing

**Check**:
1. Modal template rendered? (View page source, search for `wpapp-modal`)
2. JavaScript loaded? (Check browser console for `wpAppModal` object)
3. CSS loaded? (Check Network tab for `wpapp-modal.css`)

### AJAX Content Not Loading

**Check**:
1. AJAX URL correct?
2. Action registered? (`add_action('wp_ajax_...')`)
3. Nonce verification passing?
4. Check browser Network tab for errors

### Buttons Not Working

**Check**:
1. Button `action` attribute set?
2. Callback functions defined?
3. Browser console for JavaScript errors?

### Styling Issues

**Check**:
1. CSS loaded after modal CSS?
2. Specificity conflicts?
3. Use browser DevTools to inspect

## Examples

See `/wp-customer/examples/modal-usage-example.js` for comprehensive usage examples including:
- Add/Edit form modals
- Delete confirmations
- Success/Error messages
- DataTable integration
- Server-side handlers

## Related

- **TODO-1194**: Create centralized modal template
- **wp-app-core**: DataTable system
- **wp-customer**: Example plugin implementation
- **wp-agency**: Example plugin implementation

## Support

For issues or questions, refer to:
- `/wp-app-core/TODO/TODO-1194-create-centralized-modal-template.md`
- Example files in `/wp-customer/examples/`
- This README

---

**Last Updated**: 2025-11-01
**Author**: arisciwek
**Version**: 1.0.0
