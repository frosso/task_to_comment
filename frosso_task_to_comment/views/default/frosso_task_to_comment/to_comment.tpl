{form action=$url id=task_to_comment}

<div class="content_stack_element" >
    <div class="content_stack_element_info">
     <h3>Seleziona il task su cui inserire il commento</h3>
     </div>
    <div class="content_stack_element_body">
    {wrap field=main_task}
        <select name="merge_to_task" id="merge_to_task">
            {foreach from=$tasks item=task}
            	{if ($task.task_id != $main_task->getTaskId())}
            		<option value="{$task.task_id}">{$task.name}</option>
            	{/if}
            {/foreach}
        </select>
    {/wrap}
  </div>
</div>

<div class="content_stack_element" >
    <div class="content_stack_element_info">
     <h3>Scegli opzioni aggiuntive:</h3>
     </div>
    <div class="content_stack_element_body">
    {wrap field=options}
      <input type="hidden" id="_option_timesheet" name="options[timesheets]" value="0" />
      <input type="checkbox" id="option_timesheet" name="options[timesheets]" value="1" class="auto input_checkbox" />
      <label for="option_timesheet">&nbsp;&nbsp;&nbsp;Timesheets</label>
    {/wrap}
    {wrap field=options}
      <input type="hidden" id="_option_attachments" name="options[attachments]" value="0" />
      <input type="checkbox" id="option_attachments" name="options[attachments]" value="1" class="auto input_checkbox" />
      <label for="option_attachments">&nbsp;&nbsp;&nbsp;Copia gli allegati</label>
    {/wrap}
    {wrap field=options}
      <input type="hidden" id="_option_mantain_time" name="options[mantain_time]" value="0" />
      <input type="checkbox" id="option_mantain_time" name="options[mantain_time]" value="1" class="auto input_checkbox" />
      <label for="option_mantain_time">&nbsp;&nbsp;&nbsp;Copia l'ora di creazione nel nuovo commento</label>
    {/wrap}
    {wrap field=options}
      <input type="hidden" id="_option_to_trash" name="options[to_trash]" value="0" />
      <input type="checkbox" id="option_to_trash" name="options[to_trash]" value="1" class="auto input_checkbox" />
      <label for="option_to_trash">&nbsp;&nbsp;&nbsp;Sposta vecchio task nel cestino</label>
    {/wrap}
    {wrap field=options}
      <input type="hidden" id="_option_child_comments" name="options[child_comments]" value="0" />
      <input type="checkbox" id="option_child_comments" name="options[child_comments]" value="1" class="auto input_checkbox" />
      <label for="option_child_comments">&nbsp;&nbsp;&nbsp;Copy child comments too</label>
    {/wrap}
  </div>
</div>

{wrap_buttons}
  {submit id=merger_submit}Submit{/submit}
{/wrap_buttons}

{/form}