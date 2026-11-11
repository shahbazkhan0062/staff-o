import React from "react";

const licences = [
  // { state: "TAS", number: "283937449" },
  { state: "NSW", number: "000109203" },
  { state: "VIC", number: "922-038-30S", labourHireNo: "VICLHL09376" },
  // { state: "QLD", number: "4618808" },
];

export default function OurLicences() {
  return (
    <section className="our-licences-section">
      <style>{`
        .our-licences-section {
          --ol-blue: var(--nh-green, #0A7C6E);
          --ol-blue-dark: var(--nh-green-dark, #075E53);
          --ol-ink: var(--nh-ink, #14181C);
          --ol-muted: #6B7680;
          --ol-border: #EAEEF2;
          background: #ffffff;
          padding: 72px 0;
          font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          box-sizing: border-box;
        }

        .our-licences-section * {
          box-sizing: border-box;
        }

        .our-licences-section .ol-wrap {
          max-width: 1180px;
          margin: 0 auto;
          padding: 0 32px;
        }

        .our-licences-section h2 {
          text-align: center;
          color: var(--ol-blue);
          font-family: 'Inter', sans-serif;
          font-size: 30px;
          font-weight: 700;
          margin: 0 0 40px;
          letter-spacing: 0.01em;
        }

        .our-licences-section .ol-grid {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          gap: 24px;
        }

        .our-licences-section .ol-card {
          background: #ffffff;
          border: 1px solid var(--ol-border);
          border-top: 3px solid var(--ol-blue);
          border-radius: 18px;
          padding: 36px 24px 32px;
          text-align: center;
          flex: 1 1 250px;
          max-width: 270px;
          box-shadow: 0 10px 28px rgba(20, 24, 28, 0.06);
          transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .our-licences-section .ol-card:hover {
          transform: translateY(-3px);
          box-shadow: 0 16px 34px rgba(20, 24, 28, 0.1);
        }

        .our-licences-section .ol-state {
          font-family: Georgia, 'Times New Roman', serif;
          font-weight: 700;
          font-size: 40px;
          color: var(--ol-blue);
          margin: 0;
          line-height: 1.1;
        }

        .our-licences-section .ol-rule {
          width: 36px;
          height: 2px;
          background: var(--ol-blue);
          margin: 14px auto 22px;
          border-radius: 2px;
        }

        .our-licences-section .ol-label {
          color: var(--ol-ink);
          font-weight: 700;
          font-size: 14.5px;
          margin: 0 0 6px;
        }

        .our-licences-section .ol-number {
          color: var(--ol-muted);
          font-size: 15px;
          margin: 0;
          letter-spacing: 0.02em;
        }

        .our-licences-section .ol-label-second {
          margin-top: 14px;
        }

        @media (max-width: 520px) {
          .our-licences-section {
            padding: 56px 0;
          }
          .our-licences-section .ol-card {
            flex: 1 1 100%;
            max-width: 320px;
          }
          .our-licences-section h2 {
            font-size: 26px;
          }
        }
      `}</style>

      <div className="ol-wrap">
        <h2>Our Licences</h2>
        <div className="ol-grid">
          {licences.map((l) => (
            <div className="ol-card" key={l.state}>
              <p className="ol-state">{l.state}</p>
              <div className="ol-rule" />
              <p className="ol-label">Security Licence No:</p>
              <p className="ol-number">{l.number}</p>
              {l.labourHireNo && (
                <>
                  <p className="ol-label ol-label-second">Labour Hire No:</p>
                  <p className="ol-number">{l.labourHireNo}</p>
                </>
              )}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}