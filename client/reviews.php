<?php

namespace modules\comments\client;

use m\i18n;
use m\view;

class reviews extends comments
{
    public static $_name = '*Reviews*';

    public function _init()
    {
        if (!isset($this->view->form) || !isset($this->view->comments) || !isset($this->view->comment)) {
            return false;
        }

        i18n::init($this->module_path . '/i18n/');

        view::set('content', $this->view->comments->prepare([
            'title' => '*Real clients reviews*:',
            'form' => empty($this->user->profile) ? $this->view->authorisation_required->prepare()
                : $this->view->form->prepare(),
            'comments' => $this->get_comments('pages', $this->page->id),
            'related_model' => 'pages',
            'related_id' => $this->page->id,
        ]));

        view::set_css($this->module_path . '/css/comments.css');
        view::set_js($this->module_path . '/js/comments.js');

        return true;
    }
}