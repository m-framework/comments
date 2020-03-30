if (typeof m == 'undefined') {
    var m = function () {};
    m.fn = m.prototype = {};
}

m.fn.comments = function(context) {
var
    _data = this.data,
    field = context.find('[contenteditable="true"]').length == 0 ? context.find('textarea')
        : context.find('[contenteditable="true"]'),
    init_send = function(_field){
        if (typeof context.data['related-model'] !== 'undefined' && typeof context.data['related-id'] !== 'undefined')
            _field.on('keydown keyup', function(e){

                if (e.keyCode == 13 && !e.shiftKey && _field.html().trim().length > 0) {
                    e.preventDefault();
                    m.ajax({
                        data: {
                            action: '_ajax_save_comment',
                            related_model: context.data['related-model'],
                            related_id: context.data['related-id'],
                            comment: _field.html().trim(),
                            id: _field.attr('data-m-edit') === null ? null : _field.attr('data-m-edit'),
                            reply: _field.attr('data-m-reply') === null ? null : _field.attr('data-m-reply')
                        },
                        success: function (response) {
                            _field.html('');
                            _field.val('');
                            _field.first.textContent = '';
                            if (typeof response.comment !== 'undefined') {

                                var comment;

                                if (_field.attr('data-m-edit') !== null) {
                                    comment = m(m.to_element(response.comment));
                                    _field.closest('.comment').replace(comment.first);
                                }
                                else {
                                    /**
                                     * In case of new comment and reply
                                     */
                                    _field.after(m.to_element(response.comment));
                                    comment = _field.next('.comment');
                                    comment.after(m.to_element('<div class="replies"></div>'));
                                }

                                if (_field.attr('data-m-reply') !== null) {
                                    _field.remove();
                                }

                                if (comment !== 'undefined' && comment.length > 0) {
                                    init_delete.call(comment.first);
                                    init_edit.call(comment.first);
                                    init_reply.call(comment.first);

                                    comments_ids.push(comment.attr('data-m-id'));
                                }
                            }
                        }
                    });
                    _field.html('');
                    _field.val('');
                    _field.first.textContent = '';
                }
            });
    },
    init_delete = function(){

        var
            elem = m(this),
            id = elem.attr('data-m-id'),
            a_del = elem.find('a[href="#delete"]');

        if (a_del.length == 0)
            return false;

        a_del.on('click', function(e){

            e.preventDefault();

            if (this.getAttribute('data-m-confirm') !== null && !confirm(this.getAttribute('data-m-confirm')))
                return false;

            m.ajax({
                data: {action: '_ajax_delete_comment', id: id},
                success: function (response) {
                    if (typeof response.error !== 'undefined') {
                        console.log(response.error);
                        return false;
                    }
                    if (typeof response.result !== 'undefined' && response.result == true) {
                        var replies = elem.next('.replies');
                        elem.remove();
                        replies.remove();
                    }
                }
            });
        });
    },
    init_edit = function(){

        var
            elem = m(this),
            id = elem.attr('data-m-id'),
            a_edit = elem.find('a[href="#edit"]');

        if (a_edit.length == 0)
            return false;

        a_edit.on('click', function(e){
            e.preventDefault();

            var
                _field = field.clone(),
                comment_body = elem.find('.comment-body');

            _field.val(comment_body.html());
            _field.first.innerHTML = comment_body.html();
            //_field.first.textContent = comment_body.html();
            _field.attr('data-m-edit', id);

            init_send(_field);

            comment_body.replace(_field.first);
        });
    },
    init_reply = function(){

        var
            elem = m(this),
            id = elem.attr('data-m-id'),
            a_reply = elem.find('a[href="#reply"]');

        if (a_reply.length == 0 || elem.next('.replies').lenght > 0)
            return false;

        a_reply.on('click', function(e){
            e.preventDefault();

            var
                _field = field.clone(),
                replies = elem.next('.replies');

            _field.attr('data-m-reply', id);

            init_send(_field);

            replies.prepend(_field.first);
            _field.first.focus();
        });
    },
    comments = m('.comment[data-m-id]'),
    comment_tpl = null,
    last_id,
    last_check,
    comments_ids = [],
    refresh = function(){

        var query_obj = {
            action: '_ajax_get_comments_updates',
            need_tpl: comment_tpl == null ? 1 : 0,
            related_model: _data['related-model'],
            related_id: _data['related-id']
        };

        if (comments_ids.length > 0) {
            query_obj['ids'] = comments_ids;
        }

        if (typeof last_id !== 'undefined') {
            query_obj['last_id'] = last_id;
        }

        if (typeof last_check !== 'undefined') {
            query_obj['last_check'] = last_check;
        }

        m.ajax({
            data: query_obj,
            success: function (response) {
                if (typeof response.error !== 'undefined') {
                    console.log(response.error);
                    return false;
                }
                /**
                 * Deleting all removed comments by given ids
                 */
                if (typeof response.removed !== 'undefined' && response.removed.length > 0) {
                    for (var r = 0; r < response.removed.length; r++) {

                        var removed_comment = m('.comment[data-m-id="' + response.removed[r] + '"]');

                        if (removed_comment.length > 0) {
							var replies = removed_comment.next('.replies');
                            removed_comment.remove();
							replies.remove();

                            // trying to remove id of removed advert
                            if (comments_ids.indexOf(response.removed[r]) > -1) {
                                comments_ids.splice(comments_ids.indexOf(response.removed[r]), 1)
                            }
                        }
                    }
                }
                /**
                 * Set a template for new comments to variable `comment_tpl`
                 */
                if (typeof response.tpl !== 'undefined') {
                    comment_tpl = response.tpl;
                }

                if (typeof response.last_id !== 'undefined') {
                    last_id = response.last_id;
                }

                if (typeof response.last_check !== 'undefined') {
                    last_check = response.last_check;
                }

                /**
                 * Adding new comments into DOM tree. All new comments should be sorted ascending
                 */
                if (typeof response.comments !== 'undefined' && response.comments.length > 0) {
                    for (var c = 0; c < response.comments.length; c++) {

                        if (m('.comment[data-m-id="' + response.comments[c]['id'] + '"]').length > 0) {
                            return false;
                        }

                        (function(_tpl, obj){

                            _tpl = _tpl.replace('{id}', obj.id);
                            _tpl = _tpl.replace('{avatar}', obj.avatar);
                            _tpl = _tpl.replace('{name}', obj.name);
                            _tpl = _tpl.replace('{beauty_date}', obj.beauty_date);
                            _tpl = _tpl.replace('{comment}', obj.comment);

                            var comment = m(m.to_element(_tpl));
							
							if (typeof response.profile !== 'undefined' && typeof obj.author !== 'undefined' 
								&& typeof response.is_admin == 'undefined' 
								&& parseInt(response.profile) !== parseInt(obj.author)) {
								comment.find('a[href="#edit"]').hide();
								comment.find('a[href="#delete"]').hide();
							}

                            if (obj.reply === null) {
                                //m('.comments-block').append(comment.first);
								field.after(comment.first);
                            }
                            else {
                                var parent = m('.comment[data-m-id="' + obj.reply + '"]');
                                if (parent.length > 0 && parent.next('.replies').length > 0) {
                                    parent.next('.replies').prepend(comment.first);
                                }
                            }

                            comments_ids.push(obj.id);

                            init_delete.call(comment.first);
                            init_edit.call(comment.first);
                            init_reply.call(comment.first);

                            comment.after(m.to_element('<div class="replies"></div>'));

                            replace_links(comment.find('.comment-body').first);

                        })(comment_tpl, response.comments[c]);
                    }
                }

                /**
                 * Updating edited comments
                 */
                if (typeof response.edited !== 'undefined' && response.edited.length > 0) {
                    for (var t = 0; t < response.edited.length; t++) {

                        var edited_comment = m('.comment[data-m-id="' + response.edited[t]['id'] + '"]');

                        if (edited_comment.length == 0) {
                            return false;
                        }

                        edited_comment.find('.comment-body').html(response.edited[t]['comment']);
                        edited_comment.find('.date').html(response.edited[t]['date']);
                    }
                }
            }
        });
    },
    replace_links = function(elem){
        var rplc = function(){

            var
                url_mask = /[\s]+(http(?:s?)\:\/\/.*?)?[\s|\,\s|\<]+/gi,
                replace_el = this,
                links = this.innerHTML.replace(/<br([\s|\/])?>/gi, ' ').match(url_mask);

            if (links !== null && links instanceof Array && links.length > 0) {
                links.forEach(function(link){
                    replace_el.innerHTML = replace_el.innerHTML.replace(link.trim(), '<a href="'+link+'" target="_blank">'+link+'</a>');
                });
            }
        };
        if (typeof elem !== 'undefined' && elem instanceof HTMLElement) {
            rplc.call(elem);
        }
        else {
            m('.comment .comment-body').each(rplc);
        }
    };

    if (comments.length > 0) {
        comments.each(function(){
            init_delete.call(this);
            init_edit.call(this);
            init_reply.call(this);

            comments_ids.push(this.getAttribute('data-m-id'));
        });
    }

    init_send(field);

    replace_links();

    window.setInterval(refresh, 600000);
};