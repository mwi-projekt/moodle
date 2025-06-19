<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for DHBW International Office.
 *
 * @package     mod_dhbwio
 * @copyright   2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings
$string['pluginname'] = 'International Office';
$string['modulename'] = 'International Office';
$string['modulenameplural'] = 'International Office';
$string['dhbwioname'] = 'Name';
$string['dhbwioname_help'] = 'Name of the International Office instance';
$string['dhbwio'] = 'dhbwio';
$string['pluginadministration'] = 'International Office administration';
$string['dhbwiosettings'] = 'International Office settings';
$string['enable_map_view'] = 'Enable map view';
$string['enable_map_view_desc'] = 'Show universities on an interactive world map';
$string['enable_reports'] = 'Enable experience reports';
$string['enable_reports_desc'] = 'Allow students to submit experience reports';
$string['general_settings'] = 'Settings';
$string['general_settings_desc'] = 'Configure general settings for the International Office plugin.';
$string['enable_email_notifications'] = 'Automatic email notifications';
$string['enable_email_notifications_desc'] = 'Send email notifications to students when their applications are updated';
$string['geocoding_settings'] = 'Geocoding settings';
$string['geocoding_settings_desc'] = 'Configure settings for the address geocoding functionality.';
$string['geocoding_provider'] = 'Geocoding provider';
$string['geocoding_provider_desc'] = 'Select which service to use for converting addresses to coordinates. Note: Some providers require an API key.';
$string['geocoding_api_key'] = 'Geocoding API key';
$string['geocoding_api_key_desc'] = 'API key for the selected geocoding provider (required for Google Maps and Mapbox).';
$string['dataform_activity'] = 'DataForm Activity';
$string['dataform_activity_help'] = 'Select the DataForm activity that manages applications for this International Office module. The selected DataForm will be used to retrieve application data for utilization calculations.';
$string['no_dataform_selected'] = 'No DataForm selected';
$string['first_wish_field'] = 'First Wish Field Name';
$string['first_wish_field_help'] = 'Enter the field name in DataForm that contains the first choice university (e.g., "first_wish")';
$string['second_wish_field'] = 'Second Wish Field Name';
$string['second_wish_field_help'] = 'Enter the field name in DataForm that contains the second choice university (e.g., "second_wish")';
$string['third_wish_field'] = 'Third Wish Field Name';
$string['third_wish_field_help'] = 'Enter the field name in DataForm that contains the third choice university (e.g., "third_wish")';
$string['first_wish_weight'] = 'First Wish Weight (%)';
$string['first_wish_weight_help'] = 'Weight percentage for first choice universities in utilization calculation (0-100%)';
$string['second_wish_weight'] = 'Second Wish Weight (%)';
$string['second_wish_weight_help'] = 'Weight percentage for second choice universities in utilization calculation (0-100%)';
$string['third_wish_weight'] = 'Third Wish Weight (%)';
$string['third_wish_weight_help'] = 'Weight percentage for third choice universities in utilization calculation (0-100%)';
$string['enable_utilisation'] = 'Enable Utilization Display';
$string['enable_utilisation_help'] = 'Show utilization statistics on university detail pages';
$string['weight_range_error'] = 'Weight must be between 0 and 100 percent';
$string['invalid_field_name'] = 'Field name must start with a letter and contain only letters, numbers, and underscores';
$string['utilization_settings'] = 'Utilization Calculation Settings';
$string['utilization_settings_desc'] = 'Configure how university utilization is calculated based on application preferences';
$string['dataform_activity_id'] = 'Dataform Activity ID';
$string['dataform_activity_id_desc'] = 'The ID of the dataform activity that manages applications';
$string['first_choice_weight'] = 'First Choice Weight (%)';
$string['first_choice_weight_desc'] = 'Weight percentage for first choice universities in utilization calculation (default: 100%)';
$string['second_choice_weight'] = 'Second Choice Weight (%)';
$string['second_choice_weight_desc'] = 'Weight percentage for second choice universities in utilization calculation (default: 30%)';
$string['third_choice_weight'] = 'Third Choice Weight (%)';
$string['third_choice_weight_desc'] = 'Weight percentage for third choice universities in utilization calculation (default: 0%)';
$string['dataform_mapping'] = 'Dataform Field Mapping';
$string['dataform_mapping_desc'] = 'Map the dataform field names to university choice fields';
$string['first_choice_field'] = 'First Choice Field Name';
$string['first_choice_field_desc'] = 'The field name in dataform that contains the first choice university';
$string['second_choice_field'] = 'Second Choice Field Name';
$string['second_choice_field_desc'] = 'The field name in dataform that contains the second choice university';
$string['third_choice_field'] = 'Third Choice Field Name';
$string['third_choice_field_desc'] = 'The field name in dataform that contains the third choice university';

// Cache settings
$string['utilisation_cache_duration'] = 'Utilization Cache Duration';
$string['utilisation_cache_duration_help'] = 'How long to cache utilization calculations before recalculating';
$string['cache_5min'] = '5 minutes';
$string['cache_15min'] = '15 minutes';
$string['cache_30min'] = '30 minutes';
$string['cache_1hour'] = '1 hour';
$string['cache_2hours'] = '2 hours';
$string['cache_1day'] = '1 day';


// Address and geocoding related strings
$string['address'] = 'Street address';
$string['postal_code'] = 'Postal code';
$string['get_coordinates'] = 'Get coordinates';
$string['get_coordinates_help'] = 'Automatically retrieve latitude and longitude coordinates based on the address information.';

// Geocoding status messages
$string['geocoding_in_progress'] = 'Looking up coordinates...';
$string['geocoding_success'] = 'Coordinates found successfully!';
$string['geocoding_error'] = 'An error occurred while retrieving coordinates.';
$string['geocoding_no_results'] = 'No coordinates found for this address.';
$string['geocoding_missing_fields'] = 'City and country are required for geocoding.';
$string['geocoding_missing_api_key'] = 'API key is required for this geocoding provider.';
$string['geocoding_api_error'] = 'Geocoding API error: {$a}';

// Capabilities
$string['dhbwio:addinstance'] = 'Add a new International Office instance';
$string['dhbwio:view'] = 'View International Office content';
$string['dhbwio:manageuniversities'] = 'Manage partner universities';
$string['dhbwio:submitreport'] = 'Submit experience report';
$string['dhbwio:managetemplates'] = 'Manage email templates';
$string['dhbwio:viewreports'] = 'View reports and statistics';

// Navigation
$string['nav_universities'] = 'Partner Universities';
$string['nav_manageunis'] = 'Manage Universities';
$string['nav_reports'] = 'Experience Reports';
$string['nav_statistics'] = 'Statistics';
$string['nav_emailtemplates'] = 'Email Templates';
$string['nav_applications'] = 'Applications';
$string['nav_myapplications'] = 'My Applications';

// Universities
$string['university_name'] = 'University Name';
$string['university_country'] = 'Country';
$string['country'] = 'Country';
$string['country_help'] = 'Select the country where the university is located.';
$string['university_city'] = 'City';
$string['university_website'] = 'Website';
$string['university_website_help'] = 'Enter the website of the partner university. It should be accessible and informative for students.';
$string['university_description'] = 'Description';
$string['university_location'] = 'Location';
$string['university_coordinates'] = 'Coordinates';
$string['university_available_slots'] = 'Available Slots';
$string['university_requirements'] = 'Requirements';
$string['university_requirements_help'] = 'Specify the requirements that students must meet to study abroad at this partner university (e.g. language skills, GPA, semester level).';
$string['university_active'] = 'Active';
$string['university_active_desc'] = 'Make university visible to students';
$string['university_image'] = 'University Image';
$string['university_image_help'] = 'A representative image of the university';
$string['university_details'] = 'University Details';
$string['add_university'] = 'Add University';
$string['edit_university'] = 'Edit University';
$string['delete_university'] = 'Delete University';
$string['delete_university_confirm'] = 'Are you sure you want to delete this university?';
$string['university_saved'] = 'University saved successfully';
$string['university_deleted'] = 'University deleted successfully';
$string['no_universities'] = 'No universities available';
$string['view_details'] = 'View Details';
$string['back_to_universities'] = 'Back to universities list';
$string['basic_information'] = 'Basic Information';
$string['capacity_info'] = 'Capacity Information';
$string['status'] = 'Status';
$string['latitude'] = 'Latitude';
$string['latitude_help'] = 'Geographic latitude of the university (between -90 and 90)';
$string['longitude'] = 'Longitude';
$string['longitude_help'] = 'Geographic longitude of the university (between -180 and 180)';
$string['invalid_latitude'] = 'Invalid latitude. Value must be between -90 and 90.';
$string['invalid_longitude'] = 'Invalid longitude. Value must be between -180 and 180.';
$string['invalid_slots'] = 'Invalid number of slots. Value must be positive.';
$string['apply_for_exchange'] = 'Apply for exchange semester';

// Semester periods
$string['semester_start_month'] = 'Semester start month';
$string['semester_start_month_help'] = 'The month when the semester typically begins';
$string['semester_end_month'] = 'Semester end month';
$string['semester_end_month_help'] = 'The month when the semester typically ends';
$string['semester_period'] = 'Semester period';

// Fees
$string['semester_fees'] = 'Semester fees';
$string['semester_fees_help'] = 'The amount of fees students need to pay per semester';
$string['fee_currency'] = 'Currency';
$string['currency_eur'] = 'Euro (€)';
$string['currency_usd'] = 'US Dollar ($)';
$string['currency_gbp'] = 'British Pound (£)';
$string['currency_chf'] = 'Swiss Franc (CHF)';
$string['currency_aud'] = 'Australian Dollar (A$)';
$string['currency_cad'] = 'Canadian Dollar (C$)';
$string['currency_jpy'] = 'Japanese Yen (¥)';
$string['currency_cny'] = 'Chinese Yuan (¥)';
$string['currency_sek'] = 'Swedish Krona (SEK)';
$string['currency_nok'] = 'Norwegian Krone (NOK)';
$string['currency_dkk'] = 'Danish Krone (DKK)';
$string['currency_other'] = 'Other currency';
$string['invalid_fees'] = 'Invalid fee amount. Value must be non-negative.';

// Accommodation
$string['accommodation_type'] = 'Accommodation type';
$string['accommodation_type_help'] = 'The type of accommodation available for exchange students';
$string['accommodation_dorm'] = 'Dormitory/Student housing';
$string['accommodation_apartment'] = 'University apartments';
$string['accommodation_homestay'] = 'Homestay with local families';
$string['accommodation_hotel'] = 'Hotel/Hostel';
$string['accommodation_airbnb'] = 'Airbnb/Short-term rental';
$string['accommodation_private'] = 'Private rentals';
$string['accommodation_various'] = 'Various options available';
$string['accommodation_none'] = 'No accommodation provided';

// Experience Reports
$string['report_title'] = 'Report Title';
$string['report_content'] = 'Your Experience';
$string['report_rating'] = 'Rating (1-5)';
$string['report_rating_help'] = '1 = poor, 5 = excellent';
$string['report_submit'] = 'Submit Report';
$string['report_submitted'] = 'Report submitted successfully';
$string['report_edit'] = 'Edit Report';
$string['report_delete'] = 'Delete Report';
$string['report_delete_confirm'] = 'Are you sure you want to delete this report?';
$string['report_visible'] = 'Visible to Students';
$string['report_visible_desc'] = 'Make report visible to other students';
$string['no_reports'] = 'No experience reports available yet';
$string['no_reports_for_university'] = 'No experience reports available for this university yet';
$string['add_report'] = 'Add Experience Report';
$string['attachments'] = 'Attachments';
$string['attachments_help'] = 'You can add additional files to your experience report';
$string['select_rating'] = 'Select rating';
$string['rating_poor'] = 'Poor';
$string['rating_fair'] = 'Fair';
$string['rating_good'] = 'Good';
$string['rating_very_good'] = 'Very Good';
$string['rating_excellent'] = 'Excellent';
$string['university'] = 'University';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['by'] = 'by';
$string['rating'] = 'Rating';
$string['reports'] = 'Reports';
$string['report_semester'] = 'Semester';
$string['report_course'] = 'Course';
$string['report_attachment'] = 'Attachment';

// Visualization
$string['map_view'] = 'Map View';
$string['list_view'] = 'List View';
$string['all'] = 'All';
$string['filter'] = 'Filter';
$string['reset'] = 'Reset';
$string['actions'] = 'Actions';
$string['statistics'] = 'Statistics';
$string['kpi_dashboard'] = 'KPI Dashboard';
$string['capacity_usage'] = 'Capacity Usage';
$string['application_trends'] = 'Application Trends';
$string['popular_universities'] = 'Popular Universities';
$string['student_distribution'] = 'Student Distribution';
$string['semester_distribution'] = 'Semester Distribution';
$string['country_distribution'] = 'Country Distribution';

// Email templates
$string['email_templates'] = 'Email Templates';
$string['template_name'] = 'Template Name';
$string['template_subject'] = 'Email Subject';
$string['template_body'] = 'Email Body';
$string['template_type'] = 'Template Type';
$string['template_variables'] = 'Available Variables';
$string['template_variables_help'] = 'These variables will be replaced with actual data when the email is sent';
$string['add_template'] = 'Add Template';
$string['edit_template'] = 'Edit Template';
$string['delete_template'] = 'Delete Template';
$string['delete_template_confirm'] = 'Are you sure you want to delete this template?';
$string['template_saved'] = 'Template saved successfully';
$string['template_deleted'] = 'Template deleted successfully';
$string['template_application_received'] = 'Application Received';
$string['template_application_accepted'] = 'Application Accepted';
$string['template_application_rejected'] = 'Application Rejected';
$string['template_application_inquiry'] = 'Application Inquiry';
$string['variable_student_name'] = 'Student\'s full name';
$string['variable_student_firstname'] = 'Student\'s first name';
$string['variable_university_name'] = 'University name';
$string['variable_application_date'] = 'Application date';
$string['variable_semester'] = 'Semester applied for';
$string['variable_status'] = 'Application status';
$string['variable_comments'] = 'Comments';
$string['preview_template'] = 'Preview';
$string['send_test_email'] = 'Send Test Email';
$string['test_email_sent'] = 'Test email sent successfully';

// Application process
$string['application'] = 'Application';
$string['applications'] = 'Applications';
$string['application_form'] = 'Application Form';
$string['apply_now'] = 'Apply Now';
$string['application_status'] = 'Status';
$string['application_date'] = 'Application Date';
$string['status_draft'] = 'Draft';
$string['status_submitted'] = 'Submitted';
$string['status_under_review'] = 'Under Review';
$string['status_accepted'] = 'Accepted';
$string['status_rejected'] = 'Rejected';
$string['status_waitlisted'] = 'Waitlisted';
$string['status_inquiry'] = 'Additional Information Requested';
$string['status_withdrawn'] = 'Withdrawn';
$string['application_saved'] = 'Application saved as draft';
$string['application_submitted'] = 'Application submitted successfully';
$string['application_updated'] = 'Application updated successfully';
$string['choose_universities'] = 'Choose Universities';
$string['priority'] = 'Priority';
$string['first_choice'] = 'First Choice';
$string['second_choice'] = 'Second Choice';
$string['third_choice'] = 'Third Choice';
$string['personal_information'] = 'Personal Information';
$string['academic_information'] = 'Academic Information';
$string['course_of_study'] = 'Course of Study';
$string['current_semester'] = 'Current Semester';
$string['gpa'] = 'Grade Point Average';
$string['language_skills'] = 'Language Skills';
$string['language'] = 'Language';
$string['proficiency_level'] = 'Proficiency Level';
$string['beginner'] = 'Beginner';
$string['intermediate'] = 'Intermediate';
$string['advanced'] = 'Advanced';
$string['fluent'] = 'Fluent';
$string['native'] = 'Native';
$string['motivation'] = 'Motivation';
$string['motivation_letter'] = 'Motivation Letter';
$string['motivation_letter_help'] = 'Explain why you want to study at these universities';
$string['submit_application'] = 'Submit Application';
$string['save_draft'] = 'Save as Draft';
$string['university_already_selected'] = 'This university has already been selected';
$string['review_application'] = 'Review Application';
$string['process_application'] = 'Process Application';
$string['request_information'] = 'Request Additional Information';
$string['accept_application'] = 'Accept';
$string['reject_application'] = 'Reject';
$string['waitlist_application'] = 'Add to Waitlist';
$string['add_comment'] = 'Add Comment';
$string['comment'] = 'Comment';
$string['comments'] = 'Comments';
$string['application_history'] = 'Application History';
$string['no_applications'] = 'No applications found';
$string['confirm_status_change'] = 'Are you sure you want to change the status to {$a}?';
$string['send_notification'] = 'Send notification email';

// Form validation
$string['required'] = 'This field is required';
$string['invalid_email'] = 'Invalid email address';
$string['select_at_least_one'] = 'Please select at least one option';
$string['file_size_exceeded'] = 'File size exceeded';
$string['invalid_file_type'] = 'Invalid file type';

// Month names
$string['month_1'] = 'January';
$string['month_2'] = 'February';
$string['month_3'] = 'March';
$string['month_4'] = 'April';
$string['month_5'] = 'May';
$string['month_6'] = 'June';
$string['month_7'] = 'July';
$string['month_8'] = 'August';
$string['month_9'] = 'September';
$string['month_10'] = 'October';
$string['month_11'] = 'November';
$string['month_12'] = 'December';