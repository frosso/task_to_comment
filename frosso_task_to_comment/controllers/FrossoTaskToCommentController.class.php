<?php

// We need projects controller
AngieApplication::useController( 'project', SYSTEM_MODULE );

class FrossoTaskToCommentController extends ProjectController {
    /**
     *
     * Active module
     *
     * @var string
     */
    protected $active_module = FROSSO_TTC_MODULE;

    /**
     * Construct controller
     *
     * @param Request $parent
     * @param mixed $context
     */
    function __construct( $parent, $context = null ) {
        parent::__construct( $parent, $context );
    }

    /**
     * Prepare controller
     */
    function __before() {
        parent::__before();

        $task_id = $this->request->getId( 'task_id' );
        if ( $task_id )
            $this->active_task = Tasks::findByTaskId( $this->active_project, $task_id );
    }

    function to_comment() {
        $tasks = Tasks::findForObjectsList( $this->active_project, $this->logged_user );

        $post = $this->request->post();
        if ( !empty( $post ) ) {

            $destination_task = Tasks::findByTaskId( $this->active_project, $post['merge_to_task'] );

            if ( $destination_task ) {

                // creo il commento con lo stesso testo
                $old_body = $this->active_task->getBody();
                $comment_body = "<h2>Merged from task " . $this->active_task->getName() . "</h2><br />";
                $comment_body = $comment_body . "<small>Moved by " . $this->logged_user->getName() . "</small><br />" . $old_body;

                $destination_comment = $destination_task->comments()->newComment();
                $destination_comment->setBody( $comment_body );
                $destination_comment->setCreatedBy( $this->active_task->getCreatedBy() );
                $destination_comment->setState( STATE_VISIBLE );

                $destination_comment->setCreatedOn( DateTimeValue::now() );

                // copio l'orario di creazione originale
                if ( $post['options']['mantain_time'] == '1' ) {
                    $destination_comment->setCreatedOn( $this->active_task->getCreatedOn() );
                }

                // TODO: copiare i subscribers
                if ( isset( $post['options']['copy_subscribers'] ) && $post['options']['copy_subscribers'] == '1' ) {
                    $active_task_subscriptions = (array)$this->active_task->subscriptions()->get();
                    $main_task_subscriptions = (array)$destination_task->subscriptions()->get();

                    foreach ( $active_task_subscriptions as $key => $active_subscription_user ) {
                        foreach ( $main_task_subscriptions as $main_task_subscription_user ) {
                            if ( $active_subscription_user->getId() == $main_task_subscription_user->getId() ) {
                                unset( $active_task_subscriptions[$key] );
                                break;
                            }
                        }
                    }
                    $active_task_subscriptions = array_merge( $active_task_subscriptions, $main_task_subscriptions );
                    if ( !empty( $active_task_subscriptions ) ) {
                        $destination_task->subscriptions()->set( $active_task_subscriptions );
                    }
                }
                // FINE

                // copio i time records nel task principale
                if ( $post['options']['timesheets'] == '1' ) {
                    $cloned_task_time_records = TimeRecords::find( array(
                        'conditions' => array(
                            'parent_type = ? AND parent_id = ?',
                            'Task',
                            $this->active_task->getId()
                        )
                    ) );

                    foreach ( $cloned_task_time_records as $time_record ) {
                        $cloned_time_record = $time_record->copy();
                        $cloned_time_record->setParentId( $destination_task->getId() );
                        $new_summary = $cloned_time_record->getSummary() . " (from task " . $this->active_task->getName() . ")";
                        $cloned_time_record->setSummary( $new_summary );
                        $cloned_time_record->save();
                    }
                }

                // copio i time records nel task principale
                if ( $post['options']['child_comments'] == '1' ) {
                    $task_comments = $this->active_task->comments()->get( $this->logged_user );
                    foreach ( $task_comments as $comment ) {
                        /** @var TaskComment $cloned_comment */
                        $cloned_comment = $comment->copy();
                        $cloned_comment->setParentId( $destination_task->getId() );

                        // copy the body adding some information about the original task
                        $new_body = $cloned_comment->getBody();
                        $new_body = "<h2>Merged from task " . $this->active_task->getName() . "</h2><br />" . $new_body;
                        $cloned_comment->setBody( $new_body );

                        $cloned_comment->save();
                        // clone comment attachments too
                        $comment->attachments()->cloneTo( $cloned_comment );
                    }
                }

                // salvare commento
                $destination_comment->save();
                // salvare main_task
                $destination_task->save();
                // salvo vecchio task
                /** @var $this ->active_task Task */
                $this->active_task->save();

                // cloniamo gli allegati dopo aver salvato
                if ( $post['options']['attachments'] == '1' ) {
                    $this->active_task->attachments()->cloneTo( $destination_comment );
                    foreach ( $destination_comment->attachments()->get( $this->logged_user ) as $attachment ) {
                        /** @var $attachment IAttachmentsImplementation */
                        $attachment->setState( STATE_VISIBLE );
                    }
                    $destination_comment->save();
                    $destination_task->save();
                }

                // sposto nel cestino
                if ( $post['options']['to_trash'] == '1' ) {
                    $this->active_task->state()->trash();
                    $this->active_task->save();
                }

                $this->response->redirectToUrl( $destination_task->getViewUrl() );

                return;

            } // if main_task

        }
        // if !empty(post)

        $this->response->assign( array(
            'url'       => Router::assemble( 'task_to_comment', array(
                    'project_slug' => $this->active_task->getProject()->getSlug(),
                    'task_id'      => $this->active_task->getTaskId()
                ) ),
            'main_task' => $this->active_task,
            'tasks'     => $tasks,
        ) );
    }

}
