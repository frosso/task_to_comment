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
    function __before( ) {
        parent::__before( );

        $task_id = $this->request->getId( 'task_id' );
        if ( $task_id )
            $this->active_task = Tasks::findByTaskId( $this->active_project, $task_id );
    }

    function to_comment( ) {
        $tasks = Tasks::findForObjectsList( $this->active_project, $this->logged_user );

        $post = $this->request->post( );
        if ( !empty( $post ) ) {

            $main_task = Tasks::findByTaskId( $this->active_project, $post['merge_to_task'] );

            if ( $main_task ) {

                // creo il commento con lo stesso testo
                $old_body = $this->active_task->getBody( );
                $comment_body = "<h2>Merged from task " . $this->active_task->getName( ) . "</h2><br />";
                $comment_body = $comment_body . "<small>Moved by " . $this->logged_user->getName( ) . "</small><br />" . $old_body;

                $new_comment = $main_task->comments( )->newComment( );
                $new_comment->setBody( $comment_body );
                $new_comment->setCreatedBy( $this->active_task->getCreatedBy( ) );
                $new_comment->setState( STATE_VISIBLE );

                $new_comment->setCreatedOn( DateTimeValue::now( ) );
                
                // copio l'orario di creazione originale
                if ( $post['options']['mantain_time'] == '1' ) {
                    $new_comment->setCreatedOn( $this->active_task->getCreatedOn( ) );
                }

                // TODO: copiare i subscribers
                if ( isset($post['options']['copy_subscribers']) && $post['options']['copy_subscribers'] == '1' ) {
                    $active_task_subscriptions = (array)$this->active_task->subscriptions( )->get( );
                    $main_task_subscriptions = (array)$main_task->subscriptions( )->get( );

                    foreach ( $active_task_subscriptions as $key => $active_subscription_user ) {
                        foreach ( $main_task_subscriptions as $main_task_subscription_user ) {
                            if ( $active_subscription_user->getId( ) == $main_task_subscription_user->getId( ) ) {
                                unset( $active_task_subscriptions[$key] );
                                break;
                            }
                        }
                    }
                    $active_task_subscriptions = array_merge( $active_subscriptions, $main_task_subscriptions );
                    if ( !empty( $active_subscriptions ) ) {
                        $main_task->subscriptions( )->set( $active_subscriptions );
                    }
                }
                // FINE

                // copio i time records nel task principale
                if ( $post['options']['timesheets'] == '1' ) {
                    $merger_task_time_records = TimeRecords::find( array( 'conditions' => array(
                            'parent_type = ? AND parent_id = ?',
                            'Task',
                            $this->active_task->getId( )
                        ) ) );

                    foreach ( $merger_task_time_records as $time_record ) {
                        $copy_time_record = $time_record->copy( );
                        $copy_time_record->setParentId( $main_task->getId( ) );
                        $new_summary = $copy_time_record->getSummary( ) . " (from task " . $this->active_task->getName( ) . ")";
                        $copy_time_record->setSummary( $new_summary );
                        $copy_time_record->save( );
                    }
                }

                // sposto nel cestino
                if ( $post['options']['to_trash'] == '1' ) {
                    $this->active_task->state( )->trash( );
                }

                // salvare commento
                $new_comment->save( );
                // salvare main_task
                $main_task->save( );
                // salvo vecchio task
                $this->active_task->save( );

                // cloniamo gli allegati dopo aver salvato
                if ( $post['options']['attachments'] == '1' ) {
                    $this->active_task->attachments( )->cloneTo( $new_comment );
                    foreach ( $new_comment->attachments() as $attachment ) {
                        $attachment->setState( STATE_VISIBLE );
                    }
                }

                $this->response->redirectToUrl( $main_task->getViewUrl( ) );
                return;

            } // if main_task

        }// if !empty(post)

        $this->response->assign( array(
            'url' => Router::assemble( 'task_to_comment', array(
                'project_slug' => $this->active_task->getProject( )->getSlug( ),
                'task_id' => $this->active_task->getTaskId( )
            ) ),
            'main_task' => $this->active_task,
            'tasks' => $tasks,
        ) );
    }

}
