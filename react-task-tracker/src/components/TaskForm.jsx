import { useState } from 'react'

function TaskForm({ onAddTask }) {
  const [title, setTitle] = useState('')

  const handleSubmit = (event) => {
    event.preventDefault()
    const trimmed = title.trim()
    if (!trimmed) return
    onAddTask(trimmed)
    setTitle('')
  }

  return (
    <form className="task-form" onSubmit={handleSubmit}>
      <input
        type="text"
        name="title"
        value={title}
        placeholder="Ajouter une nouvelle tâche..."
        onChange={(event) => setTitle(event.target.value)}
      />
      <button type="submit">Ajouter</button>
    </form>
  )
}

export default TaskForm
