# medlink

The test task for the Medlink Students

Task: Create a PHP plugin or snippet that creates a WordPress shortcode that displays an HTML form. This form should allow logged-in users to send an AJAX request to update custom fields for the page from which the form is submitted. The form must only be visible to logged-in users, and the AJAX endpoint should restrict access to logged-in users exclusively.

The custom fields to be updated are:
- page_call_to_action (Text field)
- page_hero_image (URL field)
- page_intro_text (Textarea field)

You can use the free Advanced Custom Fields (ACF) plugin to create these fields and assign them to the "Page" post type. The form should include basic styling. All input data must be validated and sanitized. The form must have nonce verification and error handling mechanisms (both on the client and server side).

The plugin Medlink is created to work with any theme. 
Before install the plugin, the ACF plugin should be installed.
After install the plugin, create a shortcode [medlink] on a page. 
