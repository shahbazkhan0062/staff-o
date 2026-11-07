import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "react-toastify";
import useFetch from "../../hooks/useFetch";
import useSubmit from "../../hooks/useSubmit";
import "./PendingTasksWidget.css";

/**
 * Admin-dashboard widget: pending tasks assigned to the logged-in admin by
 * another admin. Lets them mark a task complete right from the dashboard
 * without opening the full Admin Tasks page.
 */
export default function PendingTasksWidget() {
  const { data: fetchResponse, loading, refetch } = useFetch(
    "api/admin/admin-tasks/pending-for-dashboard",
    { isAuth: true },
  );
  const { submit, loading: completing } = useSubmit({ isAuth: true });

  const [tasks, setTasks] = useState([]);
  const [pendingCount, setPendingCount] = useState(0);

  useEffect(() => {
    if (!fetchResponse?.data) return;
    setTasks(fetchResponse.data.tasks || []);
    setPendingCount(fetchResponse.data.pending_count || 0);
  }, [fetchResponse]);

  const handleComplete = async (taskId) => {
    const res = await submit(`api/admin/admin-tasks/${taskId}/status`, { status: "completed" }, { method: "PATCH" });
    if (res?.success) {
      toast.success("Task marked as completed.");
      refetch();
    }
  };

  return (
    <section className="dashboard-panel pending-tasks-panel">
      <div className="panel-heading">
        <h3>
          Pending Tasks
          {pendingCount > 0 && <span className="pending-tasks-badge">{pendingCount}</span>}
        </h3>
        <Link to="/admin-tasks" className="view-all-link">
          View All Tasks
        </Link>
      </div>

      {loading ? (
        <div className="pending-tasks-empty">Loading…</div>
      ) : tasks.length === 0 ? (
        <div className="pending-tasks-empty">You have no pending tasks. 🎉</div>
      ) : (
        <ul className="pending-tasks-list">
          {tasks.map((task) => (
            <li key={task.id} className="pending-task-row">
              <div className="pending-task-info">
                <span className="pending-task-title">{task.title}</span>
                <span className="pending-task-meta">
                  Assigned by {task.assigned_by?.name || "N/A"}
                </span>
              </div>
              <button
                type="button"
                className="pending-task-complete-btn"
                disabled={completing}
                onClick={() => handleComplete(task.id)}
              >
                Mark Complete
              </button>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
