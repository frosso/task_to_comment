<?php

class FrossoTaskToCommentModule extends AngieModule {
    /**
     * Active module
     *
     * @var string
     */
    protected $active_module = 'frosso_task_to_comment';

    /*
     * Nome del modulo, dev'essere uguale al nome della cartella
     */
    protected $name = 'frosso_task_to_comment';

    /*
     * Versione
     */
    protected $version = '0.1.1';

    /**
     * Name of the project object class (or classes) that this module uses
     *
     * @var string
     */
    //protected $project_object_classes = 'Task';

    public function getDisplayName( ) {
        return lang( 'TaskToComment - FRosso' );
    }

    public function getDescription( ) {
        return lang( "Permette di spostare i task convertendoli in commenti" );
    }

    function defineRoutes( ) {

        // Single task
        // Servono perch� se viene salvato un task tra i preferiti, viene
        // caricata la view dei tasks di default, senza il responsabile
        Router::map( 'task_to_comment', 'projects/:project_slug/tasks/:task_id/to_comment', array(
            'controller' => 'frosso_task_to_comment',
            'action' => 'to_comment'
        ), array( 'task_id' => Router::MATCH_ID ) );
    }

    function defineHandlers( ) {
        EventsManager::listen( 'on_object_options', 'on_object_options' );
    }

}
