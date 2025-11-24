import { useEffect, useMemo, useState } from 'react'
import TaskFilters from './components/TaskFilters'
import TaskForm from './components/TaskForm'
import TaskList from './components/TaskList'
import './App.css'

const STORAGE_KEY = 'task-tracker:tasks'
const FILTERS = {
  all: () => true,
  active: (task) => !task.done,
  done: (task) => task.done,
}

const createId = () => {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`
}

function App() {
  const [tasks, setTasks] = useState(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      return saved ? JSON.parse(saved) : []
    } catch (error) {
      console.warn('Impossible de lire le localStorage :', error)
      return []
    }
  })
  const [filter, setFilter] = useState('all')

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(tasks))
  }, [tasks])

  const filteredTasks = useMemo(() => tasks.filter(FILTERS[filter]), [tasks, filter])
  const remaining = tasks.filter((task) => !task.done).length

  const handleAddTask = (title) => {
    setTasks((previous) => [
      ...previous,
      { id: createId(), title, done: false, createdAt: new Date().toISOString() },
    ])
  }

  const handleToggleTask = (taskId) => {
    setTasks((previous) =>
      previous.map((task) => (task.id === taskId ? { ...task, done: !task.done } : task)),
    )
  }

  const handleDeleteTask = (taskId) => {
    setTasks((previous) => previous.filter((task) => task.id !== taskId))
  }

  const handleClearCompleted = () => {
    setTasks((previous) => previous.filter((task) => !task.done))
  }

  return (
    <div className="app-container">
      <header className="app-header">
        <p className="tagline">Mini projet React</p>
        <h1>Task Tracker</h1>
        <p>Ajoutez, filtrez et organisez vos tâches en quelques minutes.</p>
      </header>

      <section className="panel">
        <TaskForm onAddTask={handleAddTask} />

        <div className="stats">
          <span>{tasks.length} tâches</span>
          <span>{remaining} à terminer</span>
        </div>

        <TaskFilters
          activeFilter={filter}
          onFilterChange={setFilter}
          onClearCompleted={handleClearCompleted}
        />

        <TaskList
          tasks={filteredTasks}
          onToggleTask={handleToggleTask}
          onDeleteTask={handleDeleteTask}
        />
      </section>
    </div>
  )
}

export default App
