### v1.0.0 
* fix(payment-tests): correct `CoreServiceProvider` and `Menu` facade references.
* chore(payment): remove payment-form.js JavaScript file
* Add Menu facade to TestCase.php
* feat(ui): add shopping cart icon to orders menu
* refactor: Update TopicCategory thumbnail handling and simplify PaymentManager module registration
* chore: Update composer.json to include juzaweb/core as a dependency in require-dev section
* ♻️ refactor(composer.json): Update author name to "Juzaweb Team" and add "juzaweb/core" dependency
* ♻️ refactor(module): Add title field to module.json for improved clarity
* Revise README for installation and configuration steps
* Add feature tests for payment routes
* Add README.md for juzaweb/payment package
* Add unit tests for PaymentManager
* ⚙️ chore(license): Update packages license to GPL-2.0
* ✨ feat(PayPal): Add detailed error logging for purchase failures
* fix: correct migration path in payment module service provider
* refactor: call `registerMenu` directly in `PaymentServiceProvider` boot method.
* refactor: improve readability of route definitions by splitting method chains onto new lines
* refactor(payment): update payment method selector to use name attribute instead of ID
* build(payment-assets): Add compiled JavaScript and CSS assets for payment module and update webpack mix configuration.
* refactor(webpack): make asset and public paths relative to the project root.
* refactor: rename asset files for improved organization
* Remove websiteId from admin route prefix and add Order route tests
* Add Feature test for MethodController and remove websiteId from route
* Implement PayPal Webhook Handling with Signature Verification
* Add Feature test for MethodController and fix route parameter bugs
* Add feature test for Order routes and fix OrderController parameter mismatch
* Fix composer dependencies and file paths
* Add GitHub Actions workflow for running tests on PHP
* Fix namespace in TestCase for Payment module tests
* tada: Begin a project
* Add payment migration files and update PaymentServiceProvider paths
* :truck: Package folder
* Adds payment form functionality
* Improves payment processing and status tracking
* Updates payment status on success
* Adds webhook secret to Stripe configuration
* Improves payment processing and error handling
* Improves payment processing and feedback
* Enhances payment method configuration and display
* Improves Stripe payment integration
* Adds Stripe payment gateway
* Adds active column to payment methods table
* Adds sandbox mode display to payment methods
* Simplifies payment status check
* Implements payment webhook handling
* Implements embed payment method
* Adds embed payment support
* Improves payment processing and order codes
* Implements Payos payment gateway
* Adds Payos payment gateway method
* Updates handleWebhook method signature
* Improves payment flow with return URLs
* Improves payment processing and order handling
* Implements basic payment module functionality
* Adds test payment functionality
* Improves payment method form and validation
* Improves payment method management
* Implements payment method management
* Adds params to payment events and interface
* Handles payment success/failure events
* Refactors payment processing logic
* Adds PaymentCancel event
* Improves payment processing and handling
* Implements payment processing logic
* Implements basic payment module structure
* Fixes casing inconsistencies
* Implements basic payment processing functionality
* Adds payment module infrastructure
* Enables API routes and adds dependencies
* feat: Begin a project

