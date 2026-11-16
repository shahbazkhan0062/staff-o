import React, { useState } from "react";
import { Helmet } from "react-helmet";
import { useSelector } from "react-redux";
import Header from "../components/newHome/Header";
import Footer from "../components/newHome/Footer";
import CtaBand from "../components/industries/coman/CtaBand";
import "../components/industries/event-crowd-comp/styles.css";

const howToUsePageSchema = {
  "@context": "https://schema.org",
  "@type": "WebPage",
  name: "How to Use Staffoo",
  url: "https://staffoo.com.au/how-to-use",
  description:
    "Short walkthrough videos for staff, resource partners, and clients on how to get set up and use Staffoo.",
};

const breadcrumbSchema = {
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  itemListElement: [
    { "@type": "ListItem", position: 1, name: "Home", item: "https://staffoo.com.au" },
    { "@type": "ListItem", position: 2, name: "How to Use", item: "https://staffoo.com.au/how-to-use" },
  ],
};

const TABS = [
  {
    key: "staff",
    tabLabel: "Staff",
    kicker: "For Staff",
    heading: "Create your profile and pick up shifts",
    intro:
      "Watch how to sign up, verify your documents, and start accepting shifts near you in a few minutes.",
    steps: [
      "Sign up with your email and have your documents ready",
      "Complete your TFN, super, and onboarding forms",
      "Browse shifts near you and accept the ones that suit you",
    ],
    videoUrl: "https://apis.staffoo.com.au/videos/Staff%20Final.mp4",
    videoTitle: "How to use Staffoo for Staff",
    cta: {
      title: "Ready to pick up your first shift?",
      subtitle: "Create your profile, get your license verified, and start browsing shifts near you.",
      primaryBtnTextGuest: "Create your free profile",
      primaryBtnUrlGuest: "/register",
      primaryBtnTextAuth: "Go to your dashboard",
      primaryBtnUrlAuth: "/dashboard",
    },
  },
  {
    key: "partners",
    tabLabel: "Resource Partners",
    kicker: "For Resource Partners",
    heading: "Manage your team and your payouts",
    intro:
      "See how to review staff timesheets, keep your rate card up to date, and follow each payout.",
    steps: [
      "Review and approve your staff timesheets",
      "Keep your contract and state rate card current",
      "Track payouts as they land in your account",
    ],
    videoUrl: "https://apis.staffoo.com.au/videos/Resource%20Partner%20Final.mp4",
    videoTitle: "How to use Staffoo for Resource Partners",
    cta: {
      title: "Ready to manage your team on Staffoo?",
      subtitle: "Set up your partner account and start tracking timesheets and payouts in one place.",
      primaryBtnTextGuest: "Become a resource partner",
      primaryBtnUrlGuest: "/forpartner/become-partner",
      primaryBtnTextAuth: "Go to your dashboard",
      primaryBtnUrlAuth: "/dashboard",
    },
  },
  {
    key: "clients",
    tabLabel: "Clients",
    kicker: "For Clients",
    heading: "Post a job and approve the work",
    intro: "Learn how to post a job, review the guards booked on it, and sign off on completed shifts.",
    steps: [
      "Post a job with your rate, site, and start time",
      "Review the guard profile booked on your job",
      "Approve the timesheet once the shift is done",
    ],
    videoUrl: "https://apis.staffoo.com.au/videos/Client%20final.mp4",
    videoTitle: "How to use Staffoo for Clients",
    cta: {
      title: "Ready to book licensed security staff?",
      subtitle: "Post your job free, pay only once a staff member is confirmed and the shift is signed off.",
      primaryBtnTextGuest: "Post your first job",
      primaryBtnUrlGuest: "/forclients/post-job",
      primaryBtnTextAuth: "Post your first job",
      primaryBtnUrlAuth: "/forclients/post-job",
    },
  },
];

export default function HowToUse() {
  const { token } = useSelector((state) => state.auth);
  const [activeKey, setActiveKey] = useState("staff");
  const active = TABS.find((t) => t.key === activeKey) || TABS[0];

  const ctaPrimaryText = token ? active.cta.primaryBtnTextAuth : active.cta.primaryBtnTextGuest;
  const ctaPrimaryUrl = token ? active.cta.primaryBtnUrlAuth : active.cta.primaryBtnUrlGuest;

  return (
    <>
      <Helmet>
        <title>How to Use Staffoo | Quick Start Guides</title>
        <meta
          name="description"
          content="Short walkthrough videos for staff, resource partners, and clients on how to get set up and use Staffoo."
        />
        <link rel="canonical" href="https://staffoo.com.au/how-to-use" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="How to Use Staffoo | Quick Start Guides" />
        <meta
          property="og:description"
          content="Short walkthrough videos for staff, resource partners, and clients on how to get set up and use Staffoo."
        />
        <meta property="og:url" content="https://staffoo.com.au/how-to-use" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="true" />
        <link
          href="https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet"
        />
        <script type="application/ld+json">{JSON.stringify(howToUsePageSchema)}</script>
        <script type="application/ld+json">{JSON.stringify(breadcrumbSchema)}</script>
      </Helmet>

      <div className="stf-industry-page">
        {/* Navigation Header */}
        <Header />

        {/* Full-width Breadcrumb Bar */}
        <div className="stf-breadcrumb-section">
          <div className="stf-wrap">
            <div className="stf-breadcrumb">
              <a className="text-black" href="/">Home</a>
              <span className="sep">/</span>
              <span className="current">How to Use</span>
            </div>
          </div>
        </div>

        {/* Hero + role switcher */}
        <section className="stf-hero" style={{ paddingBottom: "32px" }}>
          <div className="stf-wrap">
            <div className="stf-section-head centered" style={{ maxWidth: "640px", marginBottom: "28px" }}>
              <span className="stf-eyebrow">Getting Started</span>
              <h1>How to use Staffoo</h1>
              <p className="lead" style={{ margin: "0 auto" }}>
                Short walkthrough videos for every person on the platform. Pick your role below and
                watch a quick guide on getting set up.
              </p>
            </div>

            <div
              className="stf-role-tabs"
              style={{ margin: "0 auto", maxWidth: "640px" }}
              role="tablist"
              aria-label="Choose your role"
            >
              {TABS.map((tab) => (
                <button
                  key={tab.key}
                  type="button"
                  role="tab"
                  aria-selected={activeKey === tab.key}
                  className={`stf-role-tab${activeKey === tab.key ? " active" : ""}`}
                  onClick={() => setActiveKey(tab.key)}
                >
                  {tab.tabLabel}
                </button>
              ))}
            </div>
          </div>
        </section>

        {/* Role panel: steps + video */}
        <section className="stf-section">
          <div className="stf-wrap">
            <div className="stf-howtouse-panel" role="tabpanel">
              <div className="stf-howtouse-text">
                <div className="stf-kicker">{active.kicker}</div>
                <h2>{active.heading}</h2>
                <p className="stf-howtouse-intro">{active.intro}</p>
                <ol className="stf-howtouse-steps">
                  {active.steps.map((step, idx) => (
                    <li key={idx}>
                      <span className="stf-howtouse-step-num">{idx + 1}</span>
                      <span>{step}</span>
                    </li>
                  ))}
                </ol>
              </div>

              <div className="stf-howtouse-video-card">
                <div className="stf-howtouse-video-frame">
                  <video
                    key={active.videoUrl}
                    src={active.videoUrl}
                    title={active.videoTitle}
                    controls
                    preload="metadata"
                    playsInline
                  >
                    Your browser does not support the video tag.
                  </video>
                </div>
              </div>
            </div>
          </div>
        </section>

        <style>{`
          .stf-howtouse-panel {
            display: flex;
            gap: 48px;
            align-items: center;
            flex-wrap: wrap;
          }

          .stf-howtouse-text {
            flex: 1 1 420px;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 16px;
          }

          .stf-howtouse-text h2 {
            font-size: 30px;
            color: var(--ink);
            font-family: 'Barlow Semi Condensed', sans-serif;
            font-weight: 700;
          }

          .stf-howtouse-intro {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.65;
            max-width: 460px;
          }

          .stf-howtouse-steps {
            list-style: none;
            margin: 4px 0 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
          }

          .stf-howtouse-steps li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 15px;
            color: var(--ink-soft);
            line-height: 1.5;
          }

          .stf-howtouse-step-num {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: var(--green-light);
            color: var(--green-dark);
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
            font-family: 'Inter', sans-serif;
          }

          .stf-howtouse-video-card {
            flex: 1 1 420px;
            min-width: 300px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 10px;
            background: var(--white);
            box-shadow: 0 10px 28px rgba(20, 24, 28, 0.06);
          }

          .stf-howtouse-video-frame {
            width: 100%;
            aspect-ratio: 16 / 9;
            border-radius: 10px;
            overflow: hidden;
            background: var(--ink);
          }

          .stf-howtouse-video-frame video {
            width: 100%;
            height: 100%;
            display: block;
            /* contain (not cover) so the demo screen's own UI/text is never
               cropped at the edges — any letterboxing is filled by the
               frame's dark background instead of cutting content off. */
            object-fit: contain;
            background: var(--ink);
          }

          @media (max-width: 760px) {
            .stf-howtouse-text h2 { font-size: 24px; }
          }
        `}</style>

        <CtaBand
          title={active.cta.title}
          subtitle={active.cta.subtitle}
          primaryBtnText={ctaPrimaryText}
          primaryBtnUrl={ctaPrimaryUrl}
        />

        {/* Global Footer */}
        <Footer />
      </div>
    </>
  );
}