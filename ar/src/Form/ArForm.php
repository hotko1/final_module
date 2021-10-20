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
        // Set the properties and values of the cells for the quarter and year.
        if ($i_col % 4 == 0 || $i_col == 17) {
          $form[$key_table][$i_row][$i_col] = [
            '#type' => 'number',
            '#step' => '0.01',
            '#disabled' => TRUE,
            '#value' => $form_state->getValue($key_table . '][' . $i_row . '][' . $i_col, 0),
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
    $form[$key_table] = array_reverse($form[$key_table], TRUE);
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
    // Reset the validation value.
    $reset_invalid = 0;
    $form_state->set("false_table", $reset_invalid);
    $form_state->set("true_valid", $reset_invalid);
    $form_state->set("false_invalid", $reset_invalid);
    foreach ($values as $k_table => $table) {
      // I choose from the array values only tables.
      if (is_int($k_table)) {
        $valid = 0;
        $val_0 = 0;
        $point = 0;
        foreach ($table as $i_row => $row) {
          // An array of four quarters a year.
          for ($r_q_ind = 1; $r_q_ind < 5; $r_q_ind++) {
            // An array of three months in the quarter.
            for ($r_m_ind = 1; $r_m_ind < 4; $r_m_ind++) {
              // Set the name of the variable that passes the value of
              // the month.
              $m_pre_index = ($r_q_ind - 1) * 4 + $r_m_ind;
              $m_index = "m_$m_pre_index";
              // Determine the position of the month in the table row.
              $$m_index = ($r_q_ind - 1) * 4 + $r_m_ind;

              // Adds 0 or 1 depending on whether the value of the table key is
              // even or odd. I add the opposite meaning to the table in front
              // of the current one.
              if ($k_table % 2 != 0) {
                $point = 1;
              }
              elseif ($k_table % 2 == 0) {
                $point = 0;
              }
              $point_pre = 1 - $point;
              $table_key = 'table_' . $point . '_' . $i_row . '_' . $$m_index;
              $table_key_pre = 'table_' . $point_pre . '_' . $i_row . '_' . $$m_index;
              // Got the month value of the table.
              $table_val_1 = floatval($row[$$m_index]);
              // Saved the month value of the current table, and get the value
              // from the previous table.
              $form_state->set("$table_key", $table_val_1);
              $table_val_0 = $form_state->get("$table_key_pre");

              // Validation for filling in tables for the same period.
              // I do not compare the first table with the previous one.
              if ($k_table != 0) {
                // Set the number to 1 if there is an entry in the current
                // month and no entry in the previous month, and vice versa.
                if ($table_val_0 != 0 && $table_val_1 == 0) {
                  $false_table = 1;
                  $form_state->set("false_table", $false_table);
                }
                elseif ($table_val_0 == 0 && $table_val_1 != 0) {
                  $false_table = 1;
                  $form_state->set("false_table", $false_table);
                }
              }

              // Validation for gap by months.
              // If the first full month occurs, set 1.
              $val_1 = floatval($row[$$m_index]);
              if ($val_0 != 0) {
                $valid = 1;
                $form_state->set("true_valid", $valid);
              }
              // If after an unfilled month it goes full, then check to see
              // if they have been filled before. If so then set 1.
              elseif ($val_0 == 0 && $val_1 != 0) {
                if ($valid == 1) {
                  $false_row = 1;
                  $form_state->set("false_invalid", $false_row);
                }
              }
              $val_0 = $val_1;
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
    $q_1 = 0;
    $q_2 = 0;
    $q_3 = 0;
    $q_4 = 0;
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
            // Array with quarters.
            for ($r_q_ind = 1; $r_q_ind < 5; $r_q_ind++) {
              // The variable that transmits the value for the quarter.
              $q_value = "q_$r_q_ind";
              $$q_value = 0;
              // Variable that conveys the position of the quarter in the
              // string.
              $q_index = "q_ind_$r_q_ind";
              $$q_index = 4 + ($r_q_ind - 1) * 4;
              // Variable that passes the value for the quarter in the string.
              for ($r_m_ind = 1; $r_m_ind < 4; $r_m_ind++) {
                // Set the name of the variable that passes the value of
                // the month in the quarter.
                $m_index = "m_$r_m_ind";
                // Determine the position of the month in the table row.
                $$m_index = ($r_q_ind - 1) * 4 + $r_m_ind;
                // Add value for 3 months in a quarter.
                $$q_value += floatval($row[$$m_index]);
              }
              // If the value for three months in the quarter is 0,
              // then the quarter is 0.
              if ($row[$m_1] == 0 && $row[$m_2] == 0 && $row[$m_3] == 0) {
                $$q_value = 0;
              }
              // Otherwise, calculate the value for the quarter by the formula.
              else {
                $$q_value = round((($$q_value + 1) / 3), 2);
                // Set the value for the quarter.
                $form_state->setValue($k_table . '][' . $i_row . '][' . $$q_index, floatval($$q_value));
              }
              // Add values for 4 quarters of the year.
              $y_value = $q_1 + $q_2 + $q_3 + $q_4;
              // If the value of four quarters in a year is 0, then the value
              // of the year is 0.
              if ($q_1 == 0 && $q_2 == 0 && $q_3 == 0 && $q_4 == 0) {
                $y_value = 0;
              }
              // Otherwise, calculate the value for the year by the formula.
              else {
                $y_value = round((($y_value + 1) / 4), 2);
                // Set the value for the tear.
                $form_state->setValue($k_table . '][' . $i_row . '][' . 17, floatval($y_value));
              }
            }
          }
          // Display a message about successful validation.
          \Drupal::messenger()->addStatus($this->t('Valid'));
        }
        // Print an error if the tables are not filled.
        elseif ($true_valid == 1) {
          \Drupal::messenger()->addError($this->t('Invalid'));
        }
        else {
          \Drupal::messenger()->addError($this->t('No data'));
        }
      }
    }

    // Rebuild tables.
    $form_state->setRebuild();
  }

}
