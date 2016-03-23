<?php
/**
 * Laurence F. Adams III
 * March 11 2016
 * 
 * I moved over stuff from the template.php over to this file at first (hook_form_FORM_ID_alter and it's validating handler). I want this to effect everyone, regardless of the theme being used, and I expect to be adding more features to the calendar and the event types, so it would be best to keep it a standalone module.
 * 
 */


/**
 * Implements hook_form_FORM_ID_alter().
 * 
 * Ultimately we want the Calendar Event Search to be able to filter out any Training Calendar Event with
 * "Training: All" Event Type.
 * 
 * This is the form that is displayed to the Editor who is creating or editing 
 * a calendar event. I just want to add a new validation handler so we can check
 * for 2 things:
 * 
 * #1 If the user has selected a "Training:" Event Type - if so then stuff into the $form_state the 
 * tid of "Training: All" checkbox. This will provide us with the filterability of Training Events. 
 * 
 * #2 If the user is editing a Calender Event that had a checkbox on one of it's "Training:" Event Types -
 * but the user realized its not an event for training, its an event for something else, but 134 ("Training:"),
 * was already checked, then we will remove that from the array.
 */
function mysite_calendar_features_form_calendar_item_node_form_alter(&$form, &$form_state, $form_id) {
  array_unshift($form['#validate'], "_mysite_calendar_features_extra_form_validate");
}
/**
 * Add a validation handler to the form.
 * Refer to the above for why we are creating this extra validation form.
 * 
 * #1 and #2 are where we are actually removing tid 134 ("Training:") from the $form_state
 * if the user has no "Training:" checkboxes selected in the form.
 */
function _mysite_calendar_features_extra_form_validate(&$form, &$form_state) {
    // "Training all" has a TID of 134
    $tid_training_all = 134;
    // this will be the term we are comparing against
    $training_prefix = "Training:"; 

    // Grab the event types - both the ones checked and all of them
    $field_event_types = $form['field_event_type']['und'];
    
    // event types that were checked in tid form
    $field_event_types_values_tid = $field_event_types['#value']; 
    // all event types as a possibility
    $field_event_types_options = $field_event_types['#options']; 
    // grabbing only the even types that we have selected, with their titles. 
    $selected_options = array_intersect_key($field_event_types_options, $field_event_types_values_tid);
    
    // boolean to keep track of if "Training:" occurs in the selected event types titles.
    $training_occured = false;
    foreach ($selected_options as $option => $value) {
      // we have a "Training:" occurence!
      if (($tmp = strpos($value, $training_prefix)) !== FALSE) {
        // #1 This will cover the case if the user is editing the Calendar Event that was previously saved.
        // If the editor realizes it isnt a training event, but he already saved the event with a training entry,
        // then 134 would be a entry. So lets ignore it so we can gracefully remove it if this is indeed the case.
        if($option !== $tid_training_all){
          $training_occured = true;
          break;  
        }
        
      }
    }
    // Editor selected a training occurence.    
    if ($training_occured) {
      // stuff 134 tid into the array so we check it off during processing.
      $form_state['values']['field_event_type']['und'][] = array('tid' => $tid_training_all);
    }
    // #2 they do not have any training occurences.
    else {
      // check boxes that are already checked.
      $existing_checks = array_values($form_state['values']['field_event_type']['und']);
      for ($i = 0; $i < sizeof($existing_checks); $i++) {
        // If 134 has already been selected - unselect it.
       if(($e = $existing_checks[$i]['tid']) == $tid_training_all) {
         unset($form_state['values']['field_event_type']['und'][$i]);
       }
      }
    }
}
