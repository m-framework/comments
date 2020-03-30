<?php

namespace modules\comments\admin;

use m\module;
use m\core;
use modules\comments\models\comments;

class delete extends module {

    public function _init()
    {
        $item = new comments(!empty($this->get->delete) ? $this->get->delete : null);

        if (!empty($item->id) && $this->user->is_admin() && $item->destroy()) {
            core::redirect($this->config->previous);
        }
    }
}