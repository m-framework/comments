<?php

namespace modules\comments\client;

use m\config;
use m\model;
use m\core;
use m\i18n;
use
    m\module,
    m\cache,
    m\registry,
    modules\pages\models\pages,
    m\view;

class comments extends module
{
    protected $events = [
        'article_shown' => 'init_comments_by_object',
        'product_shown' => 'init_comments_by_object',
        'advert_shown' => 'init_comments_by_object',
    ];

    public static $_name = '*Comments*';

    public function _init()
    {

    }

    public function init_comments_by_object(model $model)
    {
        if (empty($model->id) || !isset($this->view->form) || !isset($this->view->comments)
            || !isset($this->view->comment) || !empty($model->disallow_comments)) {
            return false;
        }

        i18n::init($this->module_path . '/i18n/');

        view::set('comments', $this->view->comments->prepare([
            'title' => '*Users comments*',
            'form' => empty($this->user->profile) ? $this->view->authorisation_required->prepare()
                : $this->view->form->prepare(),
            'comments' => $this->get_comments($model->table_name(), $model->id),
            'related_model' => $model->table_name(),
            'related_id' => $model->id,
        ]));

        view::set_css($this->module_path . '/css/comments.css');
        view::set_js($this->module_path . '/js/comments.js');

        return true;
    }

    public function get_comments($model, $id, $reply = null)
    {
        $comments = \modules\comments\models\comments::call_static()
            ->s([], [
                'site' => $this->site->id,
                'related_model' => $model,
                'related_id' => $id,
                'reply' => $reply,
            ], [1000])->all('object');

        $arr = [];

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $comment->replies = $this->get_comments($model, $id, $comment->id);
                $comment->comment = nl2br(stripslashes(htmlspecialchars_decode($comment->comment)));
                $comment->date_edited = empty($comment->beauty_date_edited) ? '' : $comment->beauty_date_edited;
                $arr[] = $this->view->comment->prepare($comment);
            }
        }

        return implode('', $arr);
    }

    public function _ajax_delete_comment()
    {
        if (empty($this->post->id) || empty($this->user->profile)) {
            core::out((object)['error' => 'empty important parameters']);
        }

        $comment = new \modules\comments\models\comments($this->post->id);

        if (empty($comment->author) || ((int)$comment->author!==(int)$this->user->profile && !$this->user->is_admin())){
            core::out((object)['error' => 'You have no rights for this operation']);
        }

        core::out((object)['result' => $comment->destroy()]);
    }

    public function _ajax_save_comment()
    {
        if (empty($this->post->related_model) || empty($this->post->related_id) || empty($this->post->comment)
            || empty($this->page->id) || empty($this->user->profile)) {
            core::out(['error' => 'empty important parameters']);
        }
        $comment = new \modules\comments\models\comments(empty($this->post->id) ? null : $this->post->id);

        $this->post->comment = strip_tags(htmlspecialchars_decode($this->post->comment), '<br>');

        $import_arr = [
            'comment' => $this->post->comment,
        ];

        if (empty($this->post->id) || $this->post->id == 'null') {
            $import_arr = [
                'site' => $this->site->id,
                'page' => $this->page->id,
                'related_model' => $this->post->related_model,
                'related_id' => $this->post->related_id,
                'comment' => $this->post->comment,
                'author' => $this->user->profile,
                'reply' => empty($this->post->reply) ? null : $this->post->reply,
            ];
        }
        else {
            $import_arr['date_edited'] = date('Y-m-d H:i:s');
        }

        $comment->import($import_arr);
        $comment->save();

        if ($error = $comment->error()) {
            core::out(['error' => $error]);
        }

        $comment->comment = stripslashes(htmlspecialchars_decode($comment->comment));
        $comment->date = date('Y-m-d H:i:s');

        core::out((object)['comment' => $this->view->comment->prepare($comment)]);
    }

    public function _ajax_get_comments_updates()
    {
        $res = [];

        if (!empty($this->post->need_tpl)) {
            $res['tpl'] = $this->view->comment->prepare([
                'id' => '{id}',
                'avatar' => '{avatar}',
                'name' => '{name}',
                'beauty_date' => '{beauty_date}',
                'comment' => '{comment}',
                'replies' => '',
            ]);
        }

        $cond = [
            'site' => $this->site->id,
            'page' => $this->page->id,
            'related_model' => $this->post->related_model,
            'related_id' => $this->post->related_id,
        ];

        if (!empty($this->post->last_id)) {
            $cond[] = 'id>' . $this->post->last_id;
        }

        if (!empty($this->post->last_check)) {
            $cond[] = "date>'" . $this->post->last_check . "'";
        }

        $new_comments = \modules\comments\models\comments::call_static()->s([], $cond, [10000])->all('object');

        if (!empty($new_comments)) {
            $res['comments'] = [];
            foreach ($new_comments as $new_comment) {

                $res['comments'][] = [
                    'id' => $new_comment->id,
                    'avatar' => $new_comment->avatar,
                    'name' => $new_comment->name,
                    'beauty_date' => $new_comment->beauty_date,
                    'beauty_date_edited' => $new_comment->beauty_date_edited,
                    'comment' => stripslashes(htmlspecialchars_decode($new_comment->comment)),
                    'reply' => $new_comment->reply,
					'author' => $new_comment->profile,
                ];

                $res['last_id'] = $new_comment->id;
            }
        }

        if (!empty($this->post->ids)) {
            $existed = [];
            $edited = [];

            $ids_comments = \modules\comments\models\comments::call_static()
                ->s(['id','comment','date','date_edited'], ['id' => $this->post->ids], [10000])
                ->all();

            if (!empty($ids_comments))
                foreach ($ids_comments as $ids_comment) {
                    $existed[] = $ids_comment['id'];

                    $edited_time = strtotime($ids_comment['date_edited']);

                    if (!empty($this->post->last_check) && !empty($ids_comment['date_edited'])
                        && strtotime($this->post->last_check) < $edited_time) {
                        $edited[] = [
                            'id' => $ids_comment['id'],
                            'comment' => stripslashes(htmlspecialchars_decode($ids_comment['comment'])),
                            'date' => strftime('%e %b %H:%M', strtotime($ids_comment['date'])) .
                                ' (' . i18n::get('edited') . strftime(' %e %b %H:%M', $edited_time) . ')',
                        ];
                    }
                }

            $removed = array_diff($this->post->ids, $existed);

            if (!empty($removed)) {
                $res['removed'] = array_values($removed);
            }

            if (!empty($edited)) {
                $res['edited'] = $edited;
            }
        }

        $res['last_check'] = date('Y-m-d H:i:s', time()-20);
        $res['profile'] = empty($this->user->profile) ? null : $this->user->profile;
        $res['date'] = empty($this->post->last_check) ? $res['last_check'] : date('Y-m-d H:i:s', strtotime($this->post->last_check));
		
		if (!empty($this->user->profile) && $this->user->is_admin()) {
			$res['is_admin'] = 1;
		}

//        $res['sql'] = registry::get('db_logs');

        core::out((object)$res);
    }
}