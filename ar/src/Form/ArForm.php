<?php

namespace Drupal\ar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Our custom form.
 */
class ArForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add button "Add Year".
    $form['actions']['button_year'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Year'),
      '#submit' => ['::addRowCallback'],
    ];
    // Add button "Add Table".
    $form['actions']['button_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
      '#submit' => ['::addTableCallback'],
    ];
    // Add table.
    $this->buildTable($form, $form_state);
    // Add button "Submit".
    $form['table']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Build table.
   */
  public function buildTable(array &$form, FormStateInterface $form_state) {
    $k_table = $form_state->get('k_table');
    if (empty($k_table)) {
      $k_table = 1;
      $form_state->set('k_table', $k_table);
    }
    // Add a table header.
    for ($key_table = 0; $key_table < $k_table; $key_table++) {
      $form[$key_table] = [
        '#type' => 'table',
        '#title' => 'Table form',
        '#header' => [
          $this->t('Year'),
          $this->t('Jan'),
          $this->t('Feb'),
          $this->t('Mar'),
          $this->t('Q1'),
          $this->t('Apr'),
          $this->t('May'),
          $this->t('Jun'),
          $this->t('Q2'),
          $this->t('Jul'),
          $this->t('Aug'),
          $this->t('Sep'),
          $this->t('Q3'),
          $this->t('Oct'),
          $this->t('Nov'),
          $this->t('Dec'),
          $this->t('Q4'),
          $this->t('YTD'),
        ],
      ];
      // Add rows to the table.
      $this->buildRowTable($key_table, $form, $form_state);
    }

  }

  /**
   * Build rows in the table.
   */
  public function buildRowTable(string $key_table, array &$form, FormStateInterface $form_state) {
    $k_row = $form_state->get('k_row');
    if (empty($k_row)) {
      $k_row = 1;
      $form_state->set('k_row', $k_row);
    }

    $current_year = \Drupal::time()->getCurrentTime();
    $year_out = date('Y', $current_year);

    for ($i_row = 0; $i_row < $k_row; $i_row++) {
      // Set properties to rows cells.
      $form[$key_table][$i_row]['0'] = [
        '#type' => 'textfield',
        '#value' => $year_out - $i_row,
        '#disabled' => TRUE,
      ];
      // An array of 17 cells of a row with values for month, quarter and year.
      for ($i_col = 1; $i_col <= 17; $i_col++) {
        $q_key_table = (int) $key_table;
        // I have expanded the name of the variable with which I get data
        // by quarters and years, in the opposite direction from the value of
        // the name of the variable, which I write in the table, using
        // the function array_reverse.
        // Set the name of the variable with which I get previously saved
        // results by quarters and years.
        $q_pre_revers_ind = $k_row - 1 - $i_row . '_' . $q_key_table;
        $q_revers_index = 'q_' . $i_col . '_' . $q_pre_revers_ind;
        // Set the name of the variable to which I assign the values of
        // all cells in the year.
        $q_pre_ind = $i_row . '_' . $q_key_table;
        $q_index = 'q_' . $i_col . '_' . $q_pre_ind;
        // Set the name of the variable that passes the value for a quarter.
        $q_4_ind = 'q_4_' . $q_pre_ind;
        $q_8_ind = 'q_8_' . $q_pre_ind;
        $q_12_ind = 'q_12_' . $q_pre_ind;
        $q_16_ind = 'q_16_' . $q_pre_ind;
        // Get started for the quarter.
        $$q_index = $form_state->get("q_value_$q_revers_index");
        // If there is no value for the quarter, then assign the variable 0.
        if (empty($$q_index)) {
          $$q_index = 0;
          $form_state->set("q_value_$q_revers_index", $$q_index);
        }
        // If the variables for 4 quarters are 0, then for a year equal to 0.
        elseif ($$q_4_ind == 0 && $$q_8_ind == 0 && $$q_12_ind == 0 && $$q_16_ind == 0) {
          $$q_index = 0;
        }
        // Set the properties of the cells for the quarter and year.
        if ($i_col % 4 == 0 || $i_col == 17) {
          $form[$key_table][$i_row][$i_col] = [
            '#type' => 'number',
            '#step' => '0.01',
            '#default_value' => 0,
            '#disabled' => TRUE,
            '#value' => $$q_index,
          ];
        }
        // Set properties for other cells.
        else {
          $form[$key_table][$i_row][$i_col] = [
            '#type' => 'number',
            '#step' => '0.000001',
            '#default value' => NULL,
          ];
        }
      }
      // Connecting a library with styles.
      $form['#attached']['library'][] = 'ar/ar_form';
    }

    // Return a table in reverse order.
    $form[$key_table] = array_reverse($form[$key_table]);
  }

  /**
   * Add new table.
   */
  public function addTableCallback(array &$form, FormStateInterface $form_state) {
    $k_table = ($form_state->get('k_table')) + 1;
    $form_state->set('k_table', $k_table)
      ->setRebuild();
  }

  /**
   * Add new row to the table.
   */
  public function addRowCallback(array &$form, FormStateInterface $form_state) {
    $k_row = $form_state->get('k_row') + 1;
    $form_state->set('k_row', $k_row)
      ->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->deleteAll();
    $values = $form_state->getValues();
    // Reset the validation value for one period.
    $reset_invalid = 0;
    $form_state->set("false_table", $reset_invalid);
    foreach ($values as $k_table => $table) {
      // I choose from the array values only tables.
      if (is_int($k_table)) {
        // Reset the validation value to break.
        $reset_valid = 0;
        $form_state->set("false_invalid", $reset_valid);
        $form_state->set("true_valid", $reset_valid);

        foreach ($table as $i_row => $row) {
          // An array of four quarters a year.
          for ($r_q_ind = 1; $r_q_ind < 5; $r_q_ind++) {
            // An array of three months in the quarter.
            for ($r_m_ind = 1; $r_m_ind < 4; $r_m_ind++) {
              // I determine the value of the month in the order in the table.
              // I continue the list of months in the next line 13, 14, 15, etc.
              $m_pre_value = $i_row * 12 + ($r_q_ind - 1) * 3 + $r_m_ind;
              // Set the name of the variable that passes the values for
              // the previous and this month for validation to populate
              // the tables for the same period.
              $table_value = 't_value_' . $k_table . '_' . $m_pre_value;
              $table_value_plus = 't_value_' . ($k_table + 1) . '_' . $m_pre_value;
              // Set the name of the variable that passes the values of
              // the previous and this month for validation by month break.
              $m_value = 'm_value_' . $m_pre_value;
              $m_value_plus = 'm_value_' . ($m_pre_value + 1);
              $$table_value = 0;
              $$table_value_plus = 0;
              $$m_value = 0;
              $$m_value_plus = 0;

              // Set the name of the variable that passes the value of
              // the month in the quarter.
              $m_index = "m_$r_m_ind";
              // Determine the position of the month in the table row.
              $$m_index = ($r_q_ind - 1) * 4 + $r_m_ind;
              // Assigned the value of the current month.
              $$table_value_plus = floatval($row[$$m_index]);
              $$m_value_plus = floatval($row[$$m_index]);
              // Set the value of the current month.
              $form_state->set("$table_value_plus", $$table_value_plus);
              $form_state->set("$m_value_plus", $$m_value_plus);
              // Get the value of the previous month.
              $$table_value = $form_state->get("$table_value");
              $$m_value = $form_state->get("$m_value");

              // Validation for filling in tables for the same period.
              // I do not compare the first table with the previous one.
              if ($k_table != 0) {
                // Set the number to 1 if there is an entry in the current
                // month and no entry in the previous month, and vice versa.
                if ($$table_value != 0 && $$table_value_plus == 0) {
                  $false_table = 1;
                  $form_state->set("false_table", $false_table);
                }
                elseif ($$table_value == 0 && $$table_value_plus != 0) {
                  $false_table = 1;
                  $form_state->set("false_table", $false_table);
                }
              }

              // Validation for gap by months.
              // If the first full month occurs, set 1.
              if ($$m_value != 0) {
                $$m_value = 1;
                $form_state->set("true_valid", $$m_value);
              }
              // If after an unfilled month it goes full, then check to see
              // if they have been filled before. If so then set 1.
              elseif ($$m_value == 0 && $$m_value_plus != 0) {
                $false_table = $form_state->get("true_valid");
                if ($false_table == 1) {
                  $false_table = 1;
                  $form_state->set("false_invalid", $false_table);
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $m_1 = 0;
    $m_2 = 0;
    $m_3 = 0;
    // Array with tables.
    foreach ($values as $k_table => $table) {
      // Select only tables from the values array.
      if (is_int($k_table)) {
        // Get the values set after validation.
        $false_table = $form_state->get("false_table");
        $false_invalid = $form_state->get("false_invalid");
        $true_valid = $form_state->get("true_valid");
        // Print an error if the tables are not filled in for the same period,
        // or there is a gap between the months.
        if ($false_table == 1 || $false_invalid == 1) {
          \Drupal::messenger()->addError($this->t('Invalid'));
        }
        // Carry out calculations according to formulas, if the table has
        // data and there are no validation errors.
        elseif ($true_valid == 1 && $false_table == 0) {
          // Array over the years.
          foreach ($table as $i_row => $row) {
            // Variable for the year to set the initial value to 0.
            for ($r_q_ind = 1; $r_q_ind < 5; $r_q_ind++) {
              $y_pre_index = $r_q_ind * 4 + 1;
              $y_index = 'q_' . $y_pre_index . '_' . $i_row . '_' . $k_table;
              $$y_index = 0;
            }
            // Array with quarters.
            for ($r_q_ind = 1; $r_q_ind < 5; $r_q_ind++) {
              // Set the name of the variable that passes the value of
              // the quarter to quarter depending on the year and table.
              $q_index = 'q_' . $r_q_ind * 4 . '_' . $i_row . '_' . $k_table;
              $$q_index = 0;
              // Array with months in one quarter.
              for ($r_m_ind = 1; $r_m_ind < 4; $r_m_ind++) {
                // Set the name of the variable that passes the value of
                // the month in the quarter.
                $m_index = "m_$r_m_ind";
                // Determine the position of the month in the table row.
                $$m_index = ($r_q_ind - 1) * 4 + $r_m_ind;
                // Add value for 3 months in a quarter.
                $$q_index += floatval($row[$$m_index]);
              }
              // If the value for three months in the quarter is 0,
              // then the quarter is 0.
              if ($row[$m_1] == 0 && $row[$m_2] == 0 && $row[$m_3] == 0) {
                $$q_index = 0;
              }
              // Otherwise, calculate the value for the quarter by the formula.
              else {
                $$q_index = round((($$q_index + 1) / 3), 2);
                // Add values for 4 quarters of the year.
                $$y_index += $$q_index;
              }
              // Set the value for the quarter.
              $form_state->set("q_value_$q_index", $$q_index);
            }
            // Calculate the value for the year according to the formula.
            $$y_index = round((($$y_index + 1) / 4), 2);
            $form_state->set("q_value_$y_index", $$y_index);
          }

          // Display a message about successful validation.
          \Drupal::messenger()->addStatus($this->t('Valid'));
        }
        // Print an error if the tables are not filled.
        elseif ($true_valid == 0) {
          \Drupal::messenger()->addError($this->t('Invalid'));
        }
      }
    }

    // Rebuild tables.
    $form_state->setRebuild();
  }

}
