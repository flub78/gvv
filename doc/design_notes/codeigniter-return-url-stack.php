# Managing Return URLs in CodeIgniter 2.x Using URL Stack

When building complex web applications, especially CRMs, users often navigate through multiple layers of pages (view → edit → related items → edit related item, etc.). Managing the "return" or "back" functionality properly is crucial for good user experience. This guide presents a robust solution using a URL stack stored in the session.

## Implementation Overview

The solution consists of a base controller class that implements a stack-based approach to storing and retrieving return URLs. This ensures that even with multiple layers of navigation, users always return to the correct previous page.

### Core Implementation

Create a file named `MY_Controller.php` in your `application/core` directory:

```php
<?php
/**
 * MY_Controller.php
 * Base controller extension for handling return URL stack functionality
 */
class MY_Controller extends CI_Controller {
    /**
     * Push a URL onto the return stack
     * 
     * @param string|null $url URL to store (defaults to current URL)
     * @return void
     */
    protected function push_return_url($url = null) {
        if (!$url) {
            $url = current_url();
        }
        
        $url_stack = $this->session->userdata('return_url_stack');
        if (!is_array($url_stack)) {
            $url_stack = array();
        }
        
        // Validate URL before pushing
        if ($this->validate_return_url($url)) {
            array_push($url_stack, $url);
            $this->session->set_userdata('return_url_stack', $url_stack);
            
            // Update stack timestamp
            $this->session->set_userdata('url_stack_time', time());
        }
    }
    
    /**
     * Pop the last URL from the return stack
     * 
     * @param string $default Default URL if stack is empty
     * @return string URL to redirect to
     */
    protected function pop_return_url($default = 'dashboard') {
        $this->clean_old_url_stack();
        
        $url_stack = $this->session->userdata('return_url_stack');
        
        if (!empty($url_stack)) {
            $url = array_pop($url_stack);
            $this->session->set_userdata('return_url_stack', $url_stack);
            return $url;
        }
        
        return site_url($default);
    }
    
    /**
     * Validate that a return URL is safe to use
     * 
     * @param string $url URL to validate
     * @return bool True if URL is valid
     */
    protected function validate_return_url($url) {
        // Ensure URL is internal
        if (strpos($url, base_url()) !== 0) {
            return false;
        }
        
        // Additional security checks can be added here
        // For example, checking against allowed controllers/methods
        
        return true;
    }
    
    /**
     * Clean up old URL stacks to prevent session bloat
     * 
     * @return void
     */
    protected function clean_old_url_stack() {
        $stack_time = $this->session->userdata('url_stack_time');
        if (!$stack_time || (time() - $stack_time > 3600)) {
            // Clear stack after 1 hour of inactivity
            $this->session->unset_userdata('return_url_stack');
            $this->session->unset_userdata('url_stack_time');
        }
    }
}
```

## Key Features

1. **URL Stack Management**: Instead of storing a single return URL, this implementation maintains a stack of URLs in the session, allowing for multiple levels of navigation.

2. **Security**: 
   - URLs are validated before being stored
   - Only internal URLs are allowed
   - Stack is cleaned up after period of inactivity

3. **Session Management**:
   - Automatic cleanup after 1 hour of inactivity
   - Prevents session bloat
   - Handles invalid or empty stack situations

4. **Fallback Mechanism**: If the stack is empty, redirects to a default page (dashboard by default)

## Usage Example

Here's how to use the URL stack in your controllers:

```php
class Customer extends MY_Controller {
    public function view($id) {
        // Store current URL before navigating to edit
        $this->push_return_url();
        
        // Your view logic here
        $data['customer'] = $this->customer_model->get($id);
        $this->load->view('customer/view', $data);
    }
    
    public function edit($id) {
        if ($this->input->post()) {
            // Process form submission
            $this->customer_model->update($id, $this->input->post());
            
            // Redirect back to previous page
            redirect($this->pop_return_url());
        }
        
        // Your edit form logic here
        $data['customer'] = $this->customer_model->get($id);
        $this->load->view('customer/edit', $data);
    }
}
```

## Common Scenarios

1. I am on a table list
2. I edit an element
3. I return to the table list

### More complex scenarios

1. I am on an account line table
2. I edit an accountine line
3. I add an attachment
4. I return to the account table from the backward arrow or after validation of the accounting line.
