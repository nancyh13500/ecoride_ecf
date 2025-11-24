function TaskList({ tasks, onToggleTask, onDeleteTask }) {
  if (!tasks.length) {
    return <p className="empty-state">Ajoutez une première tâche pour démarrer ✨</p>
  }

  return (
    <ul className="task-list">
      {tasks.map((task) => (
        <li key={task.id} className={task.done ? 'task done' : 'task'}>
          <label>
            <input
              type="checkbox"
              checked={task.done}
              onChange={() => onToggleTask(task.id)}
            />
            <span>{task.title}</span>
          </label>
          <button type="button" onClick={() => onDeleteTask(task.id)}>
            Supprimer
          </button>
        </li>
      ))}
    </ul>
  )
}

export default TaskList
