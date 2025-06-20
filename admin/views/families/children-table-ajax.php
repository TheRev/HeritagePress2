<table class="widefat hp-children-table" id="hp-children-table">
  <thead>
    <tr>
      <th></th>
      <th><?php esc_html_e('Person ID'); ?></th>
      <th><?php esc_html_e('Name'); ?></th>
      <th><?php esc_html_e('Birthdate'); ?></th>
      <th><?php esc_html_e('Father Rel'); ?></th>
      <th><?php esc_html_e('Mother Rel'); ?></th>
      <th><?php esc_html_e('Actions'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($children)): ?>
      <tr>
        <td colspan="7"><?php esc_html_e('No children linked to this family.'); ?></td>
      </tr>
      <?php else: foreach ($children as $child): ?>
        <tr data-person-id="<?php echo esc_attr($child['personID']); ?>" class="hp-draggable-row">
          <td class="hp-drag-handle">&#9776;</td>
          <td><?php echo esc_html($child['personID']); ?></td>
          <td><?php echo esc_html(trim(($child['firstname'] ?? '') . ' ' . ($child['lastname'] ?? ''))); ?></td>
          <td><?php echo esc_html($child['birthdate'] ?? ''); ?></td>
          <td><?php echo esc_html($child['frel'] ?? ''); ?></td>
          <td><?php echo esc_html($child['mrel'] ?? ''); ?></td>
          <td>
            <form method="post" action="" style="display:inline;">
              <input type="hidden" name="action" value="remove_child">
              <input type="hidden" name="person_id" value="<?php echo esc_attr($child['personID']); ?>">
              <input type="hidden" name="family_id" value="<?php echo esc_attr($family_id); ?>">
              <input type="hidden" name="gedcom" value="<?php echo esc_attr($gedcom); ?>">
              <button type="submit" class="button button-small">Remove</button>
            </form>
          </td>
        </tr>
    <?php endforeach;
    endif; ?>
  </tbody>
</table>
