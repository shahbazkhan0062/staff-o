import React, { useState, useMemo, useRef } from "react";
import { useSelector } from "react-redux";
import { toast } from "react-toastify";
import SignaturePad from "./contracts/SignaturePad";
import useFetch from "../hooks/useFetch";
import useSubmit from "../hooks/useSubmit";
import { apiURL } from "../utils/exports";

/**
 * "Employment Agreement" tab content for the admin Staff Profile modal
 * (pages/staffooStaff.jsx) — shown after the "Verification Forms" tab.
 *
 * This is purely a read/action panel: it does NOT generate or alter the
 * agreement PDF in any way. It only calls the already-existing endpoints
 * (send / resend / countersign / list) and shows exactly what they return.
 *
 * Uses the app's own useFetch/useSubmit hooks (isAuth: true) so the admin's
 * real auth token — held in Redux (state.auth.token) — goes out on every
 * call, the same way the rest of this page (ProfileForm, DocumentTable,
 * StaffOnboardingForms, etc.) already authenticates.
 *
 * Usage (see routes_snippet/staffooStaff_agreement_tab_snippet.jsx for the
 * exact lines to add to pages/staffooStaff.jsx):
 *   <StaffEmploymentAgreementTab userId={editingUser?.id} />
 */

const EMPLOYMENT_TYPE_LABELS = { full_time: "Full-Time", part_time: "Part-Time", casual: "Casual" };

// Backend sometimes sends a plain "d M Y" string (e.g. from getByToken()) and
// sometimes a raw ISO timestamp (e.g. "2026-11-17T13:00:00.000000Z" straight
// off a Carbon date column via the admin list endpoint) — normalize either
// into one human-readable display format instead of leaking the ISO string.
const formatDisplayDate = (value) => {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString("en-AU", { day: "numeric", month: "short", year: "numeric" });
};

export default function StaffEmploymentAgreementTab({ userId }) {
  // Sent along as admin_id so created_by / employer_signed_by are reliably
  // populated server-side, same fallback this app already uses elsewhere.
  const adminId = useSelector((state) => state.auth.userdata?.data?.id || state.auth.userdata?.id);
  // This same component is reused on the staff member's own "Employment
  // Agreement" profile tab (edit-profile.jsx) — there, the viewer is the
  // employee themselves, not an admin, so the admin-only actions (sending,
  // resending, countersigning) must not be shown to them.
  const viewerUserType = useSelector((state) => state.auth.userdata?.data?.user_type || state.auth.userdata?.user_type);
  const isAdminViewer = viewerUserType === "admin";

  const listEndpoint = useMemo(
    () => (userId ? `api/admin/employment-agreements?user_id=${userId}` : null),
    [userId]
  );
  const { data: listResponse, loading, refetch: refetchAgreement } = useFetch(listEndpoint, { isAuth: true });
  const list = listResponse?.data?.data || listResponse?.data || [];
  const agreement = Array.isArray(list) && list.length ? list[0] : null;

  const { submit: submitSend, loading: sending } = useSubmit({ isAuth: true });
  const { submit: submitResend } = useSubmit({ isAuth: true });
  const { submit: submitCounterSignReq, loading: counterSigning } = useSubmit({ isAuth: true });

  const [showCounterSign, setShowCounterSign] = useState(false);
  const [employerSignatureName, setEmployerSignatureName] = useState("");
  const sigPadRef = useRef(null);

  const sendAgreement = async () => {
    if (!userId) return;
    const json = await submitSend(`api/admin/staff/${userId}/employment-agreement/send`, { admin_id: adminId });
    if (!json?.success) return;
    toast.success(json?.message || "Employment agreement emailed to the employee for signature.");
    refetchAgreement();
  };

  const resend = async () => {
    if (!agreement) return;
    const json = await submitResend(`api/admin/employment-agreement/${agreement.id}/resend`, null);
    if (json?.success) toast.success(json?.message || "Done.");
  };

  const submitCounterSign = async () => {
    if (!agreement) return;
    const sigData = sigPadRef.current?.toDataURL();
    if (!employerSignatureName.trim() || !sigData || sigPadRef.current?.isEmpty?.()) {
      toast.error("Enter the Operations Manager's name and signature.");
      return;
    }
    const json = await submitCounterSignReq(`api/admin/employment-agreement/${agreement.id}/countersign`, {
      signature_name: employerSignatureName.trim(),
      signature_image: sigData,
      admin_id: adminId,
    });
    if (!json?.success) return;
    toast.success(json?.message || "Agreement fully executed and emailed with statutory attachments.");
    setShowCounterSign(false);
    setEmployerSignatureName("");
    refetchAgreement();
  };

  const statusBadge = (status) => {
    const map = {
      pending_signature: { label: "Pending Employee Signature", cls: "bg-warning-subtle text-warning-emphasis" },
      employee_signed: { label: "Awaiting Countersignature", cls: "bg-info-subtle text-info-emphasis" },
      fully_executed: { label: "Fully Executed", cls: "bg-success-subtle text-success-emphasis" },
      cancelled: { label: "Cancelled", cls: "bg-danger-subtle text-danger-emphasis" },
    };
    const info = map[status] || { label: status, cls: "bg-secondary-subtle text-secondary-emphasis" };
    return <span className={`badge rounded-pill px-3 py-2 ${info.cls}`}>{info.label}</span>;
  };

  if (loading) {
    return (
      <div className="text-center text-muted py-5">
        <i className="fa-solid fa-circle-notch fa-spin me-2"></i>Loading employment agreement...
      </div>
    );
  }

  if (!agreement) {
    return (
      <div className="p-4 rounded-3 border text-center" style={{ background: "#f8fafc" }}>
        <i className="fa-solid fa-file-signature fs-2 mb-3 d-block" style={{ color: "#0A7C6E" }}></i>
        <h6 className="fw-bold mb-2">No Employment Agreement Sent Yet</h6>
        {isAdminViewer ? (
          <>
            <p className="text-muted small mb-3">
              Sending fills in every Schedule 1 field automatically from this staff member's registration data —
              there's nothing to type here. They'll confirm their Employment Category (default Casual) and
              Classification Level themselves, right before they sign on their phone.
            </p>
            <button type="button" className="btn btn-dark rounded-pill px-4 fw-bold" onClick={sendAgreement} disabled={sending || !userId}>
              {sending ? "Sending..." : "Send Employment Agreement"}
            </button>
          </>
        ) : (
          <p className="text-muted small mb-0">
            You don't have an employment agreement on file yet — once Staffoo sends it, you'll get an email with a
            link to review and sign it, and it'll also show up here.
          </p>
        )}
      </div>
    );
  }

  return (
    <div>
      <div
        className="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 p-3 p-md-4 rounded-4 border"
        style={{ background: "linear-gradient(135deg, #f8fafc 0%, #eef6f4 100%)", borderColor: "#e2e8f0" }}
      >
        <div className="d-flex align-items-start gap-3">
          <div
            className="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
            style={{ width: 44, height: 44, background: "#0A7C6E1a" }}
          >
            <i className="fa-solid fa-file-signature" style={{ color: "#0A7C6E", fontSize: 18 }}></i>
          </div>
          <div>
            <h6 className="fw-bold mb-1" style={{ letterSpacing: 0.2 }}>{agreement.agreement_number}</h6>
            <div className="text-muted small d-flex flex-wrap align-items-center gap-1">
              <span>{EMPLOYMENT_TYPE_LABELS[agreement.employment_type] || agreement.employment_type}</span>
              <span className="text-body-tertiary">·</span>
              <span>Level {agreement.classification_level}</span>
              <span className="text-body-tertiary">·</span>
              <span>Commencing {formatDisplayDate(agreement.commencement_date)}</span>
            </div>
          </div>
        </div>
        {statusBadge(agreement.status)}
      </div>

      <div className="mb-3">
        {(agreement.signed_pdf_path || agreement.pdf_path) ? (
          <a
            href={`${apiURL}storage/${agreement.signed_pdf_path || agreement.pdf_path}`}
            target="_blank"
            rel="noreferrer"
            className="btn btn-dark rounded-pill px-4 fw-semibold"
          >
            <i className="fa-solid fa-file-pdf me-2"></i>View Agreement PDF
          </a>
        ) : (
          <span className="btn btn-outline-secondary disabled rounded-pill px-4 fw-semibold">
            <i className="fa-solid fa-file-pdf me-2"></i>Agreement PDF not generated yet
          </span>
        )}
      </div>

      {(agreement.fwis_attached || agreement.ceis_attached) && (
        <div className="mb-3">
          <div className="text-muted small fw-semibold mb-2" style={{ letterSpacing: 0.3, textTransform: "uppercase", fontSize: 11 }}>
            Statutory Attachments
          </div>
          <div className="d-flex flex-wrap gap-2">
            {agreement.fwis_attached && (
              <a
                href={`${apiURL}storage/statutory/fwis.pdf`}
                target="_blank"
                rel="noreferrer"
                className="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border text-decoration-none small fw-semibold"
                style={{ color: "#374151", background: "#fff" }}
              >
                <i className="fa-solid fa-file-pdf" style={{ color: "#DC2626" }}></i>Fair Work Information Statement
              </a>
            )}
            {agreement.ceis_attached && (
              <a
                href={`${apiURL}storage/statutory/ceis.pdf`}
                target="_blank"
                rel="noreferrer"
                className="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border text-decoration-none small fw-semibold"
                style={{ color: "#374151", background: "#fff" }}
              >
                <i className="fa-solid fa-file-pdf" style={{ color: "#DC2626" }}></i>Casual Employment Information Statement
              </a>
            )}
          </div>
        </div>
      )}

      <div className="d-flex flex-wrap gap-2 align-items-center">
        {isAdminViewer && agreement.status === "pending_signature" && (
          <button type="button" className="btn btn-outline-dark rounded-pill px-4 fw-semibold" onClick={resend}>
            <i className="fa-solid fa-paper-plane me-2"></i>Resend to Employee
          </button>
        )}
        {!isAdminViewer && agreement.status === "pending_signature" && (
          <span className="text-warning-emphasis small fw-semibold">
            <i className="fa-solid fa-circle-info me-1"></i>Check your email for the signing link to sign this agreement.
          </span>
        )}
        {isAdminViewer && agreement.status === "employee_signed" && (
          <button type="button" className="btn btn-dark rounded-pill px-4 fw-semibold" onClick={() => setShowCounterSign(true)}>
            <i className="fa-solid fa-signature me-2"></i>Countersign
          </button>
        )}
        {!isAdminViewer && agreement.status === "employee_signed" && (
          <span className="text-info-emphasis small fw-semibold">
            <i className="fa-solid fa-circle-info me-1"></i>You've signed — this is now awaiting Staffoo's countersignature.
          </span>
        )}
        {agreement.status === "fully_executed" && (
          <span className="text-success small fw-semibold">
            <i className="fa-solid fa-circle-check me-1"></i>Signed by both parties — emailed to employee and admin.
          </span>
        )}
      </div>

      {isAdminViewer && showCounterSign && (
        <div className="mt-4 p-3 p-md-4 rounded-3 border">
          <h6 className="fw-bold mb-2">Countersign as Director / Operations Manager</h6>
          <p className="text-muted small mb-3">
            {agreement.employee_name} has signed. This fully executes the agreement and emails the final copy
            (with FWIS{agreement.ceis_attached ? " + CEIS" : ""}) to both the employee and admin.
          </p>
          <label className="form-label fw-semibold small">Your Name</label>
          <input
            className="form-control mb-3"
            value={employerSignatureName}
            onChange={(e) => setEmployerSignatureName(e.target.value)}
            placeholder="Full name"
          />
          <label className="form-label fw-semibold small">Signature</label>
          <SignaturePad ref={sigPadRef} mode="draw" height={160} placeholderText="Draw your signature here" />
          <div className="d-flex justify-content-end gap-2 mt-3">
            <button type="button" className="btn btn-light rounded-pill px-4 fw-semibold border" onClick={() => setShowCounterSign(false)}>
              Cancel
            </button>
            <button type="button" className="btn btn-dark rounded-pill px-4 fw-bold" onClick={submitCounterSign} disabled={counterSigning}>
              {counterSigning ? "Submitting..." : "Countersign & Execute"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}