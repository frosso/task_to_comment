<?php

/**
 * task_to_comment module on_object_options event handler
 *
 * @package activeCollab.modules.frosso_task_to_comment
 * @subpackage handlers
 */

/**
 * Handle on project options event
 *
 * @param ApplicationObject $object
 * @param User $user
 * @param NamedList $options
 * @param string $interface
 */
function frosso_task_to_comment_handle_on_object_options( &$object, &$user, &$options, $interface ) {

    if ( $object instanceof Task ) {
        $options->addAfter( 'task_to_comment', array(
            'url' => Router::assemble( 'task_to_comment', array(
                'project_slug' => $object->getProject( )->getSlug( ),
                'task_id' => $object->getTaskId( )
            ) ),
            'text' => lang( 'Convert to Task Comment' ),
            // 'icon' => AngieApplication::getImageUrl( 'icons/12x12/merger.png', MERGER_MODULE ),
            'onclick' => new FlyoutCallback( array( 'width' => 600 ) )
        ) );

    }
} // frosso_task_to_comment_handle_on_object_options
