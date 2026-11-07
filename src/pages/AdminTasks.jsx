import React, { useCallback, useEffect, useState } from "react";
import { useSelector } from "react-redux";
import { useSearchParams } from "react-router-dom";
import { toast } from "react-toastify";
import useSubmit from "../hooks/useSubmit";

const TABS = [
  { key: "assigned_to_me", label: "Assigned To Me" },
  { key: "assigned_by_me", label: "Assigned By Me" },
];

const EMPTY_FORM = { title: "", description: "", assigned_to: "" };

const formatDate = (value) => {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleDateString("en-AU", { day: "2-digit", month: "short", year: "numeric" });
};

const formatDateTime = (value) => {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleString("en-AU", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

export default function AdminTasks() {
  const { userdata } = useSelector((state) => state.auth);
  const currentAdminId = userdata?.data?.id || userdata?.id;

  const [searchParams, setSearchParams] = useSearchParams();

  const { submit, loading: submitting } = useSubmit({ isAuth: true });

  const [tab, setTab] = useState("assigned_to_me");
  const [statusFilter, setStatusFilter] = useState("");
  const [tasks, setTasks] = useState([]);
  const [listLoading, setListLoading] = useState(false);

  const [admins, setAdmins] = useState([]);

  // Assign / Edit modal
  const [showForm, setShowForm] = useState(false);
  const [formMode, setFormMode] = useState("create"); // "create" | "edit"
  const [editingTaskId, setEditingTaskId] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);

  // View modal + delete confirm
  const [viewTask, setViewTask] = useState(null);
  const [viewLoading, setViewLoading] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  // Load the list of admins to assign tasks to.
  useEffect(() => {
    (async () => {
      const res = await submit("api/admin", null, { method: "GET", silentErrorToast: true });
      const list = Array.isArray(res?.data) ? res.data : [];
      setAdmins(list.filter((a) => String(a.id) !== String(currentAdminId)));
    })();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const fetchTasks = useCallback(async () => {
    setListLoading(true);
    const query = new URLSearchParams({ box: tab });
    if (statusFilter) query.append("status", statusFilter);

    const res = await submit(`api/admin/admin-tasks?${query.toString()}`, null, {
      method: "GET",
      silentErrorToast: true,
    });

    setTasks(Array.isArray(res?.data) ? res.data : []);
    setListLoading(false);
  }, [tab, statusFilter, submit]);

  useEffect(() => {
    fetchTasks();
  }, [fetchTasks]);

  // Open a task's view modal when arriving via ?task=<id> (e.g. from the
  // header's pending-tasks bell).
  const openTaskById = useCallback(
    async (taskId) => {
      setViewLoading(true);
      setViewTask({ id: taskId });
      const res = await submit(`api/admin/admin-tasks/${taskId}`, null, {
        method: "GET",
        silentErrorToast: true,
      });
      setViewLoading(false);
      if (res?.success) {
        setViewTask(res.data);
      } else {
        toast.error(res?.error || "Could not load that task.");
        setViewTask(null);
      }
    },
    [submit],
  );

  useEffect(() => {
    const taskId = searchParams.get("task");
    if (taskId) {
      openTaskById(taskId);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const closeViewModal = () => {
    setViewTask(null);
    if (searchParams.get("task")) {
      const next = new URLSearchParams(searchParams);
      next.delete("task");
      setSearchParams(next, { replace: true });
    }
  };

  const openCreateForm = () => {
    setForm(EMPTY_FORM);
    setFormMode("create");
    setEditingTaskId(null);
    setShowForm(true);
  };

  const openEditForm = (task) => {
    setForm({
      title: task.title || "",
      description: task.description || "",
      assigned_to: task.assigned_to?.id ?? task.assigned_to ?? "",
    });
    setFormMode("edit");
    setEditingTaskId(task.id);
    setShowForm(true);
    setViewTask(null);
  };

  const handleSubmitForm = async (e) => {
    e.preventDefault();

    if (!form.title.trim() || !form.assigned_to) {
      toast.error("Title and assignee are required.");
      return;
    }

    const payload = {
      title: form.title.trim(),
      description: form.description.trim() || null,
      assigned_to: Number(form.assigned_to),
    };

    const res =
      formMode === "edit"
        ? await submit(`api/admin/admin-tasks/${editingTaskId}`, payload, { method: "PUT" })
        : await submit("api/admin/admin-tasks", payload);

    if (res?.success) {
      toast.success(res.message || (formMode === "edit" ? "Task updated." : "Task assigned."));
      setForm(EMPTY_FORM);
      setShowForm(false);
      setEditingTaskId(null);
      fetchTasks();
    }
  };

  const handleStatusChange = async (taskId, status) => {
    const res = await submit(`api/admin/admin-tasks/${taskId}/status`, { status }, { method: "PATCH" });
    if (res?.success) {
      toast.success(res.message || "Task updated.");
      fetchTasks();
      if (viewTask?.id === taskId) setViewTask((t) => ({ ...t, status, completed_at: status === "completed" ? new Date().toISOString() : null }));
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    const res = await submit(`api/admin/admin-tasks/${deleteTarget.id}`, null, { method: "DELETE" });
    if (res?.success) {
      toast.success(res.message || "Task deleted.");
      setDeleteTarget(null);
      setViewTask(null);
      fetchTasks();
    }
  };

  return (
    <>
      <style>{`
        .admin-tasks-page { padding: 24px; }
        .admin-tasks-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .admin-tasks-header h2 { margin: 0; font-size: 22px; color: #1e293b; }
        .admin-tasks-assign-btn { background-color: #0a7c6e; color: #fff; border: none; border-radius: 6px; padding: 10px 18px; font-weight: 600; font-size: 14px; cursor: pointer; }
        .admin-tasks-assign-btn:hover { background-color: #086358; }
        .admin-tasks-tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .admin-tasks-tab { padding: 10px 16px; font-size: 14px; font-weight: 600; color: #64748b; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; }
        .admin-tasks-tab.active { color: #0a7c6e; border-bottom-color: #0a7c6e; }
        .admin-tasks-filters { display: flex; gap: 10px; margin-bottom: 16px; }
        .admin-tasks-filters select { padding: 8px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; }
        .admin-tasks-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .admin-tasks-table { width: 100%; border-collapse: collapse; }
        .admin-tasks-table th { text-align: left; font-size: 12px; text-transform: uppercase; color: #94a3b8; padding: 12px 16px; border-bottom: 1px solid #eef1f5; }
        .admin-tasks-table td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .admin-tasks-table tr:last-child td { border-bottom: none; }
        .task-title-cell strong { color: #1e293b; display: block; cursor: pointer; }
        .task-title-cell strong:hover { color: #0a7c6e; text-decoration: underline; }
        .task-title-cell span { color: #64748b; font-size: 12.5px; }
        .status-chip { display: inline-block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 12px; }
        .status-chip.pending { background: #fef3c7; color: #92400e; }
        .status-chip.completed { background: #d1fae5; color: #065f46; }
        .task-actions-cell { display: flex; flex-wrap: wrap; gap: 6px; }
        .task-action-btn { border: none; border-radius: 6px; padding: 7px 12px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
        .task-action-btn.complete { background: #0a7c6e; color: #fff; }
        .task-action-btn.reopen { background: #f1f5f9; color: #334155; }
        .task-icon-btn { border: none; background: #f1f5f9; color: #334155; border-radius: 6px; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12.5px; }
        .task-icon-btn:hover { background: #e2e8f0; }
        .task-icon-btn.danger:hover { background: #fee2e2; color: #b91c1c; }
        .admin-tasks-empty { padding: 40px; text-align: center; color: #94a3b8; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
        .modal-box { background: #fff; border-radius: 10px; width: 100%; max-width: 440px; padding: 24px; max-height: 90vh; overflow-y: auto; }
        .modal-box h3 { margin: 0 0 16px; font-size: 18px; color: #1e293b; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; margin-top: 14px; }
        .modal-box input, .modal-box textarea, .modal-box select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .modal-actions button { border-radius: 6px; padding: 9px 16px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; }
        .modal-actions .cancel-btn { background: #f1f5f9; color: #334155; }
        .modal-actions .submit-btn { background: #0a7c6e; color: #fff; }
        .modal-actions .submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .modal-actions .danger-btn { background: #dc2626; color: #fff; }
        .view-field { margin-bottom: 14px; }
        .view-field .vf-label { font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 3px; }
        .view-field .vf-value { font-size: 14.5px; color: #1e293b; }
      `}</style>

      <div className="admin-tasks-page">
        <div className="admin-tasks-header">
          <h2>Admin Tasks</h2>
          <button type="button" className="admin-tasks-assign-btn" onClick={openCreateForm}>
            <i className="fa-solid fa-plus" style={{ marginRight: 6 }}></i>
            Assign Task
          </button>
        </div>

        <div className="admin-tasks-tabs">
          {TABS.map((t) => (
            <button
              key={t.key}
              type="button"
              className={`admin-tasks-tab ${tab === t.key ? "active" : ""}`}
              onClick={() => setTab(t.key)}
            >
              {t.label}
            </button>
          ))}
        </div>

        <div className="admin-tasks-filters">
          <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div className="admin-tasks-card">
          <table className="admin-tasks-table">
            <thead>
              <tr>
                <th>Task</th>
                <th>{tab === "assigned_to_me" ? "Assigned By" : "Assigned To"}</th>
                <th>Status</th>
                <th>Completed</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {listLoading ? (
                <tr>
                  <td colSpan="5" className="admin-tasks-empty">Loading…</td>
                </tr>
              ) : tasks.length === 0 ? (
                <tr>
                  <td colSpan="5" className="admin-tasks-empty">No tasks found.</td>
                </tr>
              ) : (
                tasks.map((task) => {
                  const otherParty = tab === "assigned_to_me" ? task.assigned_by : task.assigned_to;
                  const isOwner = String(task.assigned_by?.id ?? task.assigned_by) === String(currentAdminId);
                  const canComplete = tab === "assigned_to_me" && task.status === "pending";
                  const canReopen = task.status === "completed";

                  return (
                    <tr key={task.id}>
                      <td className="task-title-cell">
                        <strong onClick={() => openTaskById(task.id)}>{task.title}</strong>
                        {task.description && <span>{task.description}</span>}
                      </td>
                      <td>{otherParty?.name || "N/A"}</td>
                      <td>
                        <span className={`status-chip ${task.status}`}>{task.status}</span>
                      </td>
                      <td>{formatDate(task.completed_at)}</td>
                      <td>
                        <div className="task-actions-cell">
                          <button
                            type="button"
                            className="task-icon-btn"
                            title="View"
                            onClick={() => openTaskById(task.id)}
                          >
                            <i className="fa-solid fa-eye"></i>
                          </button>

                          {canComplete && (
                            <button
                              type="button"
                              className="task-action-btn complete"
                              disabled={submitting}
                              onClick={() => handleStatusChange(task.id, "completed")}
                            >
                              Mark Complete
                            </button>
                          )}
                          {canReopen && (
                            <button
                              type="button"
                              className="task-action-btn reopen"
                              disabled={submitting}
                              onClick={() => handleStatusChange(task.id, "pending")}
                            >
                              Reopen
                            </button>
                          )}

                          {isOwner && task.status === "pending" && (
                            <>
                              <button
                                type="button"
                                className="task-icon-btn"
                                title="Edit"
                                onClick={() => openEditForm(task)}
                              >
                                <i className="fa-solid fa-pen"></i>
                              </button>
                              <button
                                type="button"
                                className="task-icon-btn danger"
                                title="Delete"
                                onClick={() => setDeleteTarget(task)}
                              >
                                <i className="fa-solid fa-trash"></i>
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Assign / Edit modal */}
      {showForm && (
        <div className="modal-backdrop" onClick={() => setShowForm(false)}>
          <div className="modal-box" onClick={(e) => e.stopPropagation()}>
            <h3>{formMode === "edit" ? "Edit Task" : "Assign New Task"}</h3>
            <form onSubmit={handleSubmitForm}>
              <label>Title</label>
              <input
                type="text"
                value={form.title}
                onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                placeholder="e.g. Review pending contractor documents"
                required
              />

              <label>Description (optional)</label>
              <textarea
                rows={3}
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                placeholder="Add any extra details"
              />

              <label>Assign To</label>
              <select
                value={form.assigned_to}
                onChange={(e) => setForm((f) => ({ ...f, assigned_to: e.target.value }))}
                required
              >
                <option value="">Select an admin…</option>
                {admins.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.name} ({a.email})
                  </option>
                ))}
              </select>

              <div className="modal-actions">
                <button type="button" className="cancel-btn" onClick={() => setShowForm(false)}>
                  Cancel
                </button>
                <button type="submit" className="submit-btn" disabled={submitting}>
                  {submitting ? "Saving…" : formMode === "edit" ? "Save Changes" : "Assign Task"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* View modal */}
      {viewTask && (
        <div className="modal-backdrop" onClick={closeViewModal}>
          <div className="modal-box" onClick={(e) => e.stopPropagation()}>
            <h3>Task Details</h3>

            {viewLoading ? (
              <p style={{ color: "#64748b" }}>Loading…</p>
            ) : (
              <>
                <div className="view-field">
                  <div className="vf-label">Title</div>
                  <div className="vf-value">{viewTask.title}</div>
                </div>

                {viewTask.description && (
                  <div className="view-field">
                    <div className="vf-label">Description</div>
                    <div className="vf-value">{viewTask.description}</div>
                  </div>
                )}

                <div className="view-field">
                  <div className="vf-label">Assigned By</div>
                  <div className="vf-value">{viewTask.assigned_by?.name || "N/A"}</div>
                </div>

                <div className="view-field">
                  <div className="vf-label">Assigned To</div>
                  <div className="vf-value">{viewTask.assigned_to?.name || "N/A"}</div>
                </div>

                <div className="view-field">
                  <div className="vf-label">Status</div>
                  <div className="vf-value">
                    <span className={`status-chip ${viewTask.status}`}>{viewTask.status}</span>
                  </div>
                </div>

                <div className="view-field">
                  <div className="vf-label">Created</div>
                  <div className="vf-value">{formatDateTime(viewTask.created_at)}</div>
                </div>

                {viewTask.status === "completed" && (
                  <div className="view-field">
                    <div className="vf-label">Completed</div>
                    <div className="vf-value">{formatDateTime(viewTask.completed_at)}</div>
                  </div>
                )}

                <div className="modal-actions" style={{ justifyContent: "space-between" }}>
                  <div style={{ display: "flex", gap: 8 }}>
                    {String(viewTask.assigned_by?.id) === String(currentAdminId) && viewTask.status === "pending" && (
                      <>
                        <button type="button" className="cancel-btn" onClick={() => openEditForm(viewTask)}>
                          Edit
                        </button>
                        <button
                          type="button"
                          className="danger-btn"
                          onClick={() => setDeleteTarget(viewTask)}
                        >
                          Delete
                        </button>
                      </>
                    )}
                  </div>
                  <div style={{ display: "flex", gap: 8 }}>
                    {String(viewTask.assigned_to?.id) === String(currentAdminId) && viewTask.status === "pending" && (
                      <button
                        type="button"
                        className="submit-btn"
                        disabled={submitting}
                        onClick={() => handleStatusChange(viewTask.id, "completed")}
                      >
                        Mark Complete
                      </button>
                    )}
                    <button type="button" className="cancel-btn" onClick={closeViewModal}>
                      Close
                    </button>
                  </div>
                </div>
              </>
            )}
          </div>
        </div>
      )}

      {/* Delete confirmation */}
      {deleteTarget && (
        <div className="modal-backdrop" onClick={() => setDeleteTarget(null)}>
          <div className="modal-box" onClick={(e) => e.stopPropagation()} style={{ maxWidth: 380 }}>
            <h3>Delete Task?</h3>
            <p style={{ color: "#475569", fontSize: 14 }}>
              Are you sure you want to delete <strong>{deleteTarget.title}</strong>? This cannot be undone.
            </p>
            <div className="modal-actions">
              <button type="button" className="cancel-btn" onClick={() => setDeleteTarget(null)}>
                Cancel
              </button>
              <button type="button" className="danger-btn" disabled={submitting} onClick={handleDelete}>
                {submitting ? "Deleting…" : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
