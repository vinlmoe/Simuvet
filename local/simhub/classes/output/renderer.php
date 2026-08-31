<?php

namespace local_simhub\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer SimHub.
 */
class renderer extends \plugin_renderer_base {

    /**
     * @param student_home_page $page
     * @return string
     */
    public function render_student_home_page(student_home_page $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('local_simhub/student_home', $data);
    }
}
