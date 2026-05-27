<?php
// Smart Date Filter Modal Partial
?>
<!-- Smart Date Filter Modal -->
<div id="smart-date-modal" class="modal-backdrop" style="display:none; justify-content:center; align-items:center; z-index:9999;">
  <div class="modal" style="max-width: 600px; width: 95%; display: flex; flex-direction: column; overflow: hidden; background: var(--bg2); border: 1px solid var(--border); box-shadow: var(--shadow);">
    <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding: 16px 20px; border-bottom: 1px solid var(--border)">
      <h3 class="modal-title" style="margin:0; font-size: 1.05rem; font-weight:700;">📅 Select Date Range</h3>
      <button type="button" class="btn-close-date-modal" style="color:var(--text2); font-size: 1.5rem; cursor:pointer;" onclick="closeSmartDateModal()">&times;</button>
    </div>
    <div class="modal-body" style="padding:0; display:flex; flex-direction:row; height:380px;">
      
      <!-- Presets List (Left Side) -->
      <div style="flex: 1; border-right: 1px solid var(--border); overflow-y:auto; padding: 8px 0; background: var(--bg);">
        <button type="button" class="date-preset-btn" data-preset="today">Today</button>
        <button type="button" class="date-preset-btn" data-preset="yesterday">Yesterday</button>
        <button type="button" class="date-preset-btn" data-preset="last_7">Last 7 Days</button>
        <button type="button" class="date-preset-btn" data-preset="last_14">Last 14 Days</button>
        <button type="button" class="date-preset-btn" data-preset="last_21">Last 21 Days</button>
        <button type="button" class="date-preset-btn" data-preset="last_28">Last 28 Days</button>
        <button type="button" class="date-preset-btn" data-preset="current_month">Current Month</button>
        <button type="button" class="date-preset-btn" data-preset="last_3_months">Last 3 Months</button>
        <button type="button" class="date-preset-btn" data-preset="this_year">This Year</button>
        <button type="button" class="date-preset-btn" data-preset="last_year">Last Year</button>
        <button type="button" class="date-preset-btn" data-preset="custom" style="border-top:1px solid var(--border); font-weight:700;">Custom Date Range</button>
      </div>

      <!-- Custom Inputs (Right Side) -->
      <div id="date-custom-panel" style="flex: 1.2; padding: 20px; display:flex; flex-direction:column; justify-content:space-between; background: var(--bg2);">
        <div>
          <h4 style="margin-top:0; margin-bottom:16px; font-size:.9rem; font-weight:600; color:var(--text)">Custom Selection</h4>
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">Start Date</label>
            <input type="date" id="date-start-input" class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" id="date-end-input" class="form-input">
          </div>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
          <button type="button" class="btn btn-outline btn-sm" onclick="closeSmartDateModal()">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm" onclick="applyCustomSmartDate()">Apply</button>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.date-preset-btn {
  display: block;
  width: 100%;
  padding: 10px 18px;
  text-align: left;
  background: transparent;
  border: none;
  color: var(--text2);
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}
.date-preset-btn:hover {
  background: var(--bg3);
  color: var(--text);
}
.date-preset-btn.active {
  background: rgba(99,102,241,0.12);
  color: var(--accent);
  font-weight: 600;
}
@media (max-width: 600px) {
  #smart-date-modal .modal-body {
    flex-direction: column !important;
    height: auto !important;
    max-height: 80vh;
    overflow-y: auto;
  }
  #smart-date-modal div[style*="border-right"] {
    border-right: none !important;
    border-bottom: 1px solid var(--border);
    flex: none !important;
    height: 180px;
  }
}
</style>

<script>
let currentDateTrigger = null;

function openSmartDateModal(triggerElement) {
  currentDateTrigger = triggerElement;
  const modal = document.getElementById('smart-date-modal');
  modal.style.display = 'flex';
  modal.classList.add('open');

  // Load existing values into start/end inputs if set
  const container = triggerElement.closest('.smart-date-filter');
  const fromVal = container.querySelector('.smart-from-val').value;
  const toVal = container.querySelector('.smart-to-val').value;

  document.getElementById('date-start-input').value = fromVal;
  document.getElementById('date-end-input').value = toVal;

  // Highlight active preset if applicable
  const activePreset = container.dataset.preset || '';
  document.querySelectorAll('.date-preset-btn').forEach(btn => {
    if (btn.dataset.preset === activePreset) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });
}

function closeSmartDateModal() {
  const modal = document.getElementById('smart-date-modal');
  modal.style.display = 'none';
  modal.classList.remove('open');
}

function applyCustomSmartDate() {
  const startVal = document.getElementById('date-start-input').value;
  const endVal = document.getElementById('date-end-input').value;
  if (!startVal || !endVal) {
    alert('Please select both start and end dates.');
    return;
  }
  saveSmartDateRange(startVal, endVal, 'custom');
}

function saveSmartDateRange(start, end, presetLabel) {
  if (!currentDateTrigger) return;
  const container = currentDateTrigger.closest('.smart-date-filter');
  container.querySelector('.smart-from-val').value = start;
  container.querySelector('.smart-to-val').value = end;
  container.dataset.preset = presetLabel;

  // Automatically submit the form
  const form = container.closest('form');
  if (form) {
    form.submit();
  } else {
    closeSmartDateModal();
  }
}

// Preset Handler
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.date-preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const preset = this.dataset.preset;
      if (preset === 'custom') {
        // Just focus custom fields
        document.getElementById('date-start-input').focus();
        return;
      }
      
      const today = new Date();
      const formatDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const r = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${r}`;
      };

      let start, end;
      switch(preset) {
        case 'today':
          start = today; end = today;
          break;
        case 'yesterday':
          start = new Date(); start.setDate(today.getDate() - 1);
          end = new Date(); end.setDate(today.getDate() - 1);
          break;
        case 'last_7':
          start = new Date(); start.setDate(today.getDate() - 6);
          end = today;
          break;
        case 'last_14':
          start = new Date(); start.setDate(today.getDate() - 13);
          end = today;
          break;
        case 'last_21':
          start = new Date(); start.setDate(today.getDate() - 20);
          end = today;
          break;
        case 'last_28':
          start = new Date(); start.setDate(today.getDate() - 27);
          end = today;
          break;
        case 'current_month':
          start = new Date(today.getFullYear(), today.getMonth(), 1);
          end = today;
          break;
        case 'last_3_months':
          start = new Date(); start.setMonth(today.getMonth() - 3);
          end = today;
          break;
        case 'this_year':
          start = new Date(today.getFullYear(), 0, 1);
          end = today;
          break;
        case 'last_year':
          start = new Date(today.getFullYear() - 1, 0, 1);
          end = new Date(today.getFullYear() - 1, 11, 31);
          break;
      }

      if (start && end) {
        saveSmartDateRange(formatDate(start), formatDate(end), preset);
      }
    });
  });

  // Attach click handler to any trigger button
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.smart-date-btn');
    if (btn) {
      e.preventDefault();
      openSmartDateModal(btn);
    }
  });
});
</script>
