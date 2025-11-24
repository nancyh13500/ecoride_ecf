const FILTER_LABELS = {
  all: 'Toutes',
  active: 'En cours',
  done: 'Terminées',
}

function TaskFilters({ activeFilter, onFilterChange, onClearCompleted }) {
  return (
    <div className="task-filters">
      <div className="filter-buttons">
        {Object.entries(FILTER_LABELS).map(([key, label]) => (
          <button
            key={key}
            type="button"
            className={key === activeFilter ? 'selected' : ''}
            onClick={() => onFilterChange(key)}
          >
            {label}
          </button>
        ))}
      </div>
      <button type="button" className="clear-completed" onClick={onClearCompleted}>
        Nettoyer les terminées
      </button>
    </div>
  )
}

export default TaskFilters
