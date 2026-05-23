<?php
session_start();
include('header.php');
?>
<meta name="author" content="Sunam Ghosh --@SunamGhosh">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style type="text/css">
    *, *::before, *::after { box-sizing: border-box !important; }

    :root {
        --primary: #6366f1;
        --primary-lt: #a5b4fc;
        --primary-dk: #4338ca;
        --purple: #a855f7;
        --purple-dk: #7e22ce;
        --blue: #3b82f6;
        --blue-dk: #1d4ed8;
        --teal: #0ea5e9;
        --bg-main: #f8fafc;
        --card-bg: rgba(255, 255, 255, .9);
        --text: #1e293b;
        --muted: #64748b;
        --border: rgba(99, 102, 241, .12);
        --radius-lg: 24px;
        --radius-md: 16px;
        --shadow-md: 0 4px 18px -2px rgba(99, 102, 241, .10);
        --shadow-lg: 0 12px 32px -4px rgba(99, 102, 241, .15);
    }

    body {
        background-color: var(--bg-main) !important;
        background-image:
            radial-gradient(at 0% 0%, rgba(147, 197, 253, .2) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(196, 181, 253, .15) 0px, transparent 50%),
            radial-gradient(at 50% 0%, rgba(186, 230, 253, .1) 0px, transparent 40%) !important;
        font-family: 'Outfit', sans-serif !important;
        color: var(--text);
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
        padding-top: 0 !important; /* Reset header padding */
    }

    /* Hide default headers */
    .header1, .header2, .header3, #mu-menu {
        display: none !important;
    }

    #feePage {
        max-width: 1000px;
        margin: 14px auto;
        padding: 0 12px 60px;
    }

    /* ── Animations ─────────────────────────────── */
    @keyframes shimmer {
        0% { transform: translateX(-100%) skewX(-15deg); }
        100% { transform: translateX(400%) skewX(-15deg); }
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .3), 0 4px 15px rgba(0, 0, 0, .15); }
        50% { box-shadow: 0 0 0 6px rgba(255, 255, 255, .08), 0 4px 20px rgba(0, 0, 0, .2); }
    }

    /* ── College Branding ────────────────────────── */
    .college-branding {
        text-align: center;
        background: #fff;
        padding: 12px 15px;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        border: 1px solid rgba(0, 0, 0, .05);
        border-bottom: none;
        animation: fadeUp .8s ease-out;
    }
    .college-name {
        font-weight: 800;
        font-size: 2rem;
        color: #ce1126;
        letter-spacing: .02em;
        text-transform: uppercase;
        display: block;
        line-height: 1.1;
        margin-bottom: 4px;
    }
    .college-affiliation {
        font-size: .85rem;
        color: #000;
        font-weight: 700;
        display: block;
        white-space: nowrap;
    }

    /* ── Page Header ─────────────────────────────── */
    .page-header {
        background: linear-gradient(135deg, #1e1b6e 0%, #312e81 35%, #4f46e5 70%, #818cf8 100%);
        padding: 24px 28px !important;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        color: #fff !important;
        margin-top: -1px;
        margin-bottom: 25px !important;
        box-shadow: 0 20px 40px -8px rgba(49, 46, 129, .35);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        animation: fadeUp .6s ease-out both;
    }
    .page-header::before {
        content: ''; position: absolute; top: -60px; right: -60px;
        width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(255, 255, 255, .15) 0%, transparent 65%);
        border-radius: 50%; pointer-events: none;
    }
    .page-header::after {
        content: ''; position: absolute; top: 0; left: 0;
        width: 60px; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .15), transparent);
        animation: shimmer 4s ease-in-out infinite;
        pointer-events: none;
    }
    .ph-title {
        font-weight: 800; font-size: 1.5rem;
        letter-spacing: -.02em; text-shadow: 0 2px 10px rgba(0, 0, 0, .2);
        line-height: 1.2; z-index: 2;
    }
    .ph-title span {
        display: block; font-size: .85rem; font-weight: 400;
        opacity: .85; margin-top: 5px;
    }

    .header-icon-pill {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(255, 255, 255, .15); backdrop-filter: blur(10px);
        padding: 8px 18px; border-radius: 50px;
        border: 1.5px solid rgba(255, 255, 255, .3);
        font-size: .75rem; font-weight: 700; color: #fff;
        text-transform: uppercase; z-index: 2;
        animation: pulse 3s ease-in-out infinite;
    }
    .header-icon-pill i { font-size: .9rem; }

    /* ── Payment Cards Grid ─────────────────────── */
    .payment-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        animation: fadeUp .5s ease-out both;
        animation-delay: .2s;
    }

    .pay-card {
        background: #fff;
        border-radius: var(--radius-md);
        padding: 24px;
        border: 1.5px solid var(--border);
        box-shadow: var(--shadow-md);
        transition: all .3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .pay-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-lt);
    }

    .card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 18px;
    }
    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .icon-purple { background: #f5f3ff; color: var(--purple); }
    .icon-blue { background: #eff6ff; color: var(--blue); }

    .card-title {
        font-weight: 800;
        font-size: 1.15rem;
        color: var(--text);
        margin: 0;
    }
    .card-desc {
        font-size: .85rem;
        color: var(--muted);
        line-height: 1.5;
        margin-bottom: 22px;
    }

    /* ── Buttons ────────────────────────────────── */
    .btn-pay {
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        transition: all .25s;
        border: none;
        text-decoration: none !important;
        margin-bottom: 12px;
    }
    .btn-purple {
        background: linear-gradient(135deg, var(--purple), var(--purple-dk));
        color: #fff !important;
        box-shadow: 0 8px 20px -6px rgba(168, 85, 247, .4);
    }
    .btn-purple:hover { transform: scale(1.02); box-shadow: 0 12px 24px -6px rgba(168, 85, 247, .5); }

    .btn-blue {
        background: linear-gradient(135deg, var(--blue), var(--blue-dk));
        color: #fff !important;
        box-shadow: 0 8px 20px -6px rgba(59, 130, 246, .4);
    }
    .btn-blue:hover { transform: scale(1.02); box-shadow: 0 12px 24px -6px rgba(59, 130, 246, .5); }

    .btn-outline {
        background: transparent;
        border: 1.5px solid #e2e8f0;
        color: var(--text);
        padding: 10px;
        border-radius: 10px;
        font-weight: 600;
        font-size: .8rem;
        width: 100%;
        cursor: pointer;
        transition: all .2s;
    }
    .btn-outline:hover { background: #f8fafc; border-color: var(--primary-lt); color: var(--primary); }

    /* ── Collapse Content ───────────────────────── */
    .collapse-wrap {
        margin-top: 15px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .steps-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .step-item {
        display: flex;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .step-item:last-child { border-bottom: none; }
    .step-num {
        width: 24px; height: 24px;
        background: var(--primary-lt);
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 800; flex-shrink: 0;
    }
    .step-text { font-size: .82rem; font-weight: 600; color: var(--text); line-height: 1.4; }

    .tip-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 12px;
        font-size: .78rem;
        color: #166534;
        display: flex; align-items: center; gap: 8px;
    }

    /* ── Bank Table ────────────────────────────── */
    .bank-table-frame {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        border: 1.5px solid #e2e8f0;
        margin-top: 12px;
    }
    .bank-table { width: 100%; border-collapse: collapse; }
    .bank-table th {
        width: 35%;
        background: #f1f5f9;
        padding: 10px 14px;
        font-size: .72rem;
        font-weight: 800;
        color: var(--muted);
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
    }
    .bank-table td {
        padding: 10px 14px;
        font-size: .82rem;
        font-weight: 700;
        color: var(--text);
        border-bottom: 1px solid #e2e8f0;
    }
    .bank-table tr:last-child th, .bank-table tr:last-child td { border-bottom: none; }

    .qr-wrap { text-align: center; margin: 15px 0; }
    .qr-img {
        max-width: 160px;
        border: 4px solid #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,.1);
    }

    /* ── Responsive ──────────────────────────────── */
    @media (max-width: 768px) {
        .payment-grid { grid-template-columns: 1fr; gap: 15px; }
        .college-name { font-size: 1.2rem !important; }
        .college-affiliation { font-size: .65rem !important; white-space: normal !important; }
        .ph-title { font-size: 1.15rem !important; }
        .ph-title span { font-size: .75rem !important; }
        .header-icon-pill { display: none; }
    }
</style>

<div id="feePage">

    <!-- ── College Branding ── -->
    <div class="college-branding">
        <span class="college-name">GIIT PROFESSIONAL COLLEGE</span>
        <span class="college-affiliation">Affiliated to KOLHAN UNIVERSITY | Approved by HRD, Govt. of Jharkhand</span>
    </div>

    <!-- ── Page Header ── -->
    <div class="page-header">
        <div class="ph-title">
            Fee Payment Portal
            <span>Securely pay your college fees online</span>
        </div>
        <div class="header-icon-pill">
            <i class="fa fa-shield-halved"></i>
            Secure Gateway
        </div>
    </div>

    <!-- ── Payment Options Grid ── -->
    <div class="payment-grid">
        
        <!-- Online Payment Card -->
        <div class="pay-card">
            <div class="card-header">
                <div class="card-icon icon-purple">
                    <i class="fa fa-credit-card"></i>
                </div>
                <h2 class="card-title">Online Payment</h2>
            </div>
            <p class="card-desc">Pay instantly using UPI, Credit Card, Debit Card, or Net Banking via our secure payment gateway.</p>
            
            <form action="https://payments.mygiit.com/index.php" method="POST">
                <input type="hidden" name="PAYMENT" value="<?= base64_encode('college-fee') ?>">
                <input type="hidden" name="PAYMENT_TYPE" value="<?= base64_encode('COLLEGE') ?>">
                <input type="hidden" name="REDIRECT" value="https://mygiit.com/fee">
                <button type="submit" class="btn-pay btn-purple">
                    <i class="fa fa-bolt"></i> Pay Now (Instant)
                </button>
            </form>

            <button class="btn-outline" data-toggle="collapse" data-target="#onlineSteps">
                <i class="fa fa-info-circle"></i> View Payment Steps
            </button>

            <div id="onlineSteps" class="collapse">
                <div class="collapse-wrap">
                    <ul class="steps-list">
                        <li class="step-item">
                            <span class="step-num">1</span>
                            <span class="step-text">Click 'Pay Now'. You'll be redirected to our portal.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">2</span>
                            <span class="step-text">Login with credentials and enter the amount.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">3</span>
                            <span class="step-text">Confirm your Mobile & Email, then click Continue.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">4</span>
                            <span class="step-text">Select a payment option (UPI/Card) and complete the process.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">5</span>
                            <span class="step-text">Receive instant confirmation from GIIT.</span>
                        </li>
                    </ul>
                    <div class="tip-box">
                        <i class="fa fa-lightbulb"></i>
                        <span><b>Tip:</b> Use <b>UPI</b> for zero convenience fees.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Transfer Card -->
        <div class="pay-card">
            <div class="card-header">
                <div class="card-icon icon-blue">
                    <i class="fa fa-bank"></i>
                </div>
                <h2 class="card-title">Bank / QR Transfer</h2>
            </div>
            <p class="card-desc">Transfer fees directly to the college account or scan the QR code using GPay, PhonePe, or Paytm.</p>
            
            <div style="display: flex; gap: 10px; flex-direction: column;">
                <button class="btn-pay btn-blue" data-toggle="modal" data-target="#qrModal">
                    <i class="fa fa-qrcode"></i> Scan QR Code
                </button>

                <button class="btn-outline" data-toggle="collapse" data-target="#bankDetails">
                    <i class="fa fa-bank"></i> View Bank Details
                </button>

                <button class="btn-outline" data-toggle="collapse" data-target="#bankSteps">
                    <i class="fa fa-question-circle"></i> How to pay via App?
                </button>
            </div>

            <!-- Bank Details Collapse -->
            <div id="bankDetails" class="collapse">
                <div class="collapse-wrap">
                    <div class="bank-table-frame">
                        <table class="bank-table">
                            <tr><th>A/C Name</th><td>GIIT PROFESSIONAL COLLEGE</td></tr>
                            <tr><th>A/C No.</th><td>7161398132</td></tr>
                            <tr><th>Bank</th><td>INDIAN BANK</td></tr>
                            <tr><th>IFSC</th><td>IDIB000J006</td></tr>
                            <tr><th>Branch</th><td>JAMSHEDPUR</td></tr>
                        </table>
                    </div>
                    <p style="font-size: .7rem; color: var(--muted); margin-top: 10px; text-align: center;">
                        <i class="fa fa-info-circle"></i> Use these details for IMPS/NEFT/RTGS transfers.
                    </p>
                </div>
            </div>

            <!-- Bank Steps Collapse -->
            <div id="bankSteps" class="collapse">
                <div class="collapse-wrap">
                    <p style="font-size:.75rem;font-weight:700;color:var(--blue-dk);margin-bottom:8px;">Via GPay / Paytm / PhonePe:</p>
                    <ul class="steps-list">
                        <li class="step-item">
                            <span class="step-num">1</span>
                            <span class="step-text">Open your payment app and select 'Bank Transfer'.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">2</span>
                            <span class="step-text">Enter the Account Number and IFSC code shown above.</span>
                        </li>
                        <li class="step-item">
                            <span class="step-num">3</span>
                            <span class="step-text">Enter the amount and confirm the transfer.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div><!-- /grid -->

    <!-- ── QR Modal ── -->
    <div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 450px;">
            <div class="modal-content" style="border-radius: var(--radius-lg); border: none; overflow: hidden; background: rgba(255,255,255,0.98); backdrop-filter: blur(25px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
                <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.05); padding: 18px 24px; background: rgba(255,255,255,0.5);">
                    <h5 class="modal-title" style="font-weight: 800; color: var(--text); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fa fa-qrcode" style="color: var(--blue);"></i> Scan & Pay
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="outline: none; opacity: 0.5;">
                        <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px; text-align: center;">
                    <!-- QR Code Area -->
                    <div class="qr-wrap" style="margin: 0 auto 15px;">
                        <img src="/assets/img/online-fee/gpay-money-transfer-new.jpg" alt="QR Code" class="qr-img" style="max-width: 180px; height: auto; border: 6px solid #fff; border-radius: 18px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                    </div>
                    <p style="font-weight: 800; color: var(--text); margin-bottom: 4px; font-size: .95rem; text-transform: uppercase; letter-spacing: 0.5px;">GIIT PROFESSIONAL COLLEGE</p>
                    <p style="font-size: .75rem; color: var(--muted); margin-bottom: 20px;">Scan with GPay, PhonePe, Paytm or any UPI App</p>
                    
                    <!-- Divider with Text -->
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
                        <span style="font-size: .65rem; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: 1px;">Or Bank Transfer</span>
                        <div style="flex: 1; height: 1px; background: #e2e8f0;"></div>
                    </div>

                    <!-- Bank Details Table -->
                    <div class="bank-table-frame" style="border: 1px solid #edf2f7; border-radius: 12px; overflow: hidden; background: #fff;">
                        <table class="bank-table" style="width: 100%; border-collapse: collapse; font-size: .8rem;">
                            <tr style="border-bottom: 1px solid #f7fafc;">
                                <th style="padding: 10px 15px; background: #f8fafc; color: var(--muted); font-weight: 700; width: 35%; text-align: left;">A/C Name</th>
                                <td style="padding: 10px 15px; color: var(--text); font-weight: 700; text-align: left;">GIIT PROF. COLLEGE</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f7fafc;">
                                <th style="padding: 10px 15px; background: #f8fafc; color: var(--muted); font-weight: 700; text-align: left;">A/C No.</th>
                                <td style="padding: 10px 15px; color: var(--text); font-weight: 700; text-align: left; font-family: 'Courier New', Courier, monospace; letter-spacing: 1px;">7161398132</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f7fafc;">
                                <th style="padding: 10px 15px; background: #f8fafc; color: var(--muted); font-weight: 700; text-align: left;">Bank</th>
                                <td style="padding: 10px 15px; color: var(--text); font-weight: 700; text-align: left;">INDIAN BANK</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f7fafc;">
                                <th style="padding: 10px 15px; background: #f8fafc; color: var(--muted); font-weight: 700; text-align: left;">IFSC</th>
                                <td style="padding: 10px 15px; color: var(--primary); font-weight: 800; text-align: left; letter-spacing: 0.5px;">IDIB000J006</td>
                            </tr>
                            <tr>
                                <th style="padding: 10px 15px; background: #f8fafc; color: var(--muted); font-weight: 700; text-align: left;">Branch</th>
                                <td style="padding: 10px 15px; color: var(--text); font-weight: 700; text-align: left;">JAMSHEDPUR</td>
                            </tr>
                        </table>
                    </div>

                    <div class="tip-box" style="justify-content: center; margin-top: 18px; padding: 8px 12px; background: rgba(16, 185, 129, 0.06); border-color: rgba(16, 185, 129, 0.1); color: #059669; font-size: .7rem;">
                        <i class="fa fa-shield-check"></i>
                        <span style="font-weight: 700;">Verified College Payment Gateway</span>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 15px 24px;">
                    <button type="button" class="btn-pay btn-purple" data-dismiss="modal" style="margin: 0; padding: 10px 20px; font-size: .85rem;">Close Window</button>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: .8rem; color: var(--muted); opacity: .7;">
        <i class="fa fa-info-circle"></i> For any payment related queries, please contact the college office.
    </div>

</div><!-- /#feePage -->

<?php include('footer.php'); ?>