<?php

namespace Desktop\Api;

use Api\IBase;

interface IDesktop extends IBase {

	/**
	 * Add a task to the desktop
	 * @param $tasktype string type of the task for selecting the correct template (i.e. xrm entry tag, ...)
	 * @param $taskdata array of data for the task (i.e. xrm entry id, priority, answer type, possible answers, ...)
	 * @param $users array of user ids for processing
	 * @return task id
	 */
	public function addTask($tasktype, $taskdata, $users);
	// TODO
	// Alles wird im Template umgesetzt: (ggfs. noch Interface erzeugen für task, um Schnittstelle für Freigabe-Abfrage zu haben usw.)
	// Ein Task Type besteht aus lib-Klasse und tpl-Template
	// - Prioritäten
	// - Wartezeit bis Freigabe des Ergebnisses (um ggfs. rückgängig machen zu können)
	// - Bedingung zur Freigabe bei getResults

	/*
	 * Get number of tasks
	 */
	public function numTasks($tasktype);

	/*
	 * Get number of waiting tasks
	 */
	public function waitingTasks($tasktype);

	/*
	 * Delete a task by id
	 */
	public function removeTask($taskid);

	/*
	 * Delete a list of tasks by task type
	 */
	public function removeTasks($tasktype);

	/**
	 * Get results from the desktop
	 * @param $tasktype string type of the task for selecting the results of a special type
	 * @return result data (array) for working with the result (i.e. xrm entry id, result)
	 */
	public function getResults($tasktype);
}
