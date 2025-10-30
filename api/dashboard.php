<?php
// stay/dashboard.php
declare(strict_types=1);
session_start();

// If you want server-side gatekeeping:
if (empty($_SESSION['user'])) {
  header('Location: login.html'); // fallback if JS hasn't redirected yet
  exit;
}

// Pull current user for initial paint (JS will re-check via api/me.php)
$user = $_SESSION['user'] ?? [];
$name = htmlspecialchars($user['name'] ?? $user['email'] ?? 'User', ENT_QUOTES);
$role = htmlspecialchars($user['role_name'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - StayFind</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <!-- Tailwind (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- App base (optional): if hosting in a subfolder like /stay/, set base -->
  <base href="/stay/">

  <!-- Fav minimal styles for scrollbars (optional) -->
  <style>
    ::-webkit-scrollbar{height:10px;width:10px}::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:10px}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r hidden md:flex md:flex-col">
      <div class="px-5 py-4 border-b">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded bg-blue-700"></div>
          <div>
            <p class="text-sm text-gray-500">Signed in as</p>
            <p id="signedInName" class="font-semibold text-gray-800"><?= $name ?></p>
            <p id="signedInRole" class="text-xs text-gray-500"><?= $role ?></p>
          </div>
        </div>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
          <span>🏠</span> <span>Dashboard</span>
        </a>
        <a href="#units" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
          <span>🏘️</span> <span>Units</span>
        </a>
        <a href="settings.html" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100">
          <span>⚙️</span> <span>Settings</span>
        </a>
      </nav>

      <div class="px-3 py-4 border-t">
        <button id="logoutBtn" class="w-full px-3 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-800">
          Logout
        </button>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1">
      <!-- Mobile header -->
      <header class="md:hidden bg-white border-b sticky top-0 z-10">
        <div class="px-4 py-3 flex items-center justify-between">
          <h1 class="font-semibold text-blue-700">StayFind</h1>
          <div class="flex items-center gap-3">
            <a href="settings.html" class="px-3 py-1.5 rounded border hover:bg-gray-50 text-sm">Settings</a>
            <button id="logoutBtnMobile" class="px-3 py-1.5 rounded bg-blue-700 text-white text-sm hover:bg-blue-800">Logout</button>
          </div>
        </div>
      </header>

      <!-- Content -->
      <section class="mx-auto max-w-6xl px-4 py-6 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
            <p class="text-sm text-gray-600">Browse and manage units. Use filters to narrow results.</p>
          </div>
          <button
            id="addUnitBtn"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-800 disabled:bg-gray-300 disabled:cursor-not-allowed"
            title="Only admins/owners/managers can add units"
          >
            ➕ Add Unit
          </button>
        </div>

        <!-- Filters -->
        <div class="bg-white border rounded-xl p-4">
          <div class="grid gap-4 md:grid-cols-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
              <select id="filterLocation" class="w-full border rounded-lg px-3 py-2">
                <option value="">All locations</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Min price (₱)</label>
              <input id="filterMinPrice" type="range" min="0" max="10000" step="100" value="0" class="w-full">
              <div class="text-xs text-gray-600">₱ <span id="minPriceLabel">0</span></div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Max price (₱)</label>
              <input id="filterMaxPrice" type="range" min="0" max="20000" step="100" value="20000" class="w-full">
              <div class="text-xs text-gray-600">₱ <span id="maxPriceLabel">20000</span></div>
            </div>
          </div>
        </div>

        <!-- Grid -->
        <div id="units" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
        <p id="empty" class="hidden text-center text-gray-500">No units found.</p>
      </section>
    </main>
  </div>

  <!-- Unit modal (no image picker) -->
  <div id="unitModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg overflow-hidden">
      <div class="px-5 py-3 border-b flex items-center justify-between">
        <h3 id="unitModalTitle" class="text-lg font-semibold">Add Unit</h3>
        <button id="unitModalClose" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>

      <form id="unitForm" class="p-5 space-y-4" enctype="multipart/form-data">
        <input type="hidden" name="id">
        <input type="hidden" name="existing_image_path">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
          <input name="title" required class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Location (text)</label>
            <input name="location" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
            <input name="capacity" type="number" min="0" class="w-full border rounded-lg px-3 py-2" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Location Link (Google Maps/Earth URL)</label>
          <input name="location_link" placeholder="https://maps.google.com/... or https://earth.google.com/..." class="w-full border rounded-lg px-3 py-2" />
          <p class="text-xs text-gray-500 mt-1">Paste a full https:// link from Google Maps or Google Earth.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
            <input name="price_per_night" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Image (optional)</label>
            <!-- Keep input for API compatibility; no preview/picker JS -->
            <input name="image" type="file" accept="image/*" class="w-full border rounded-lg px-3 py-2 bg-white" />
            <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP up to 5MB (optional).</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" id="unitCancel" class="px-4 py-2 rounded-lg border hover:bg-gray-50">Cancel</button>
          <button id="unitSubmit" class="px-4 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-800">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View modal -->
  <div id="viewModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-2xl rounded-xl shadow-lg overflow-hidden">
      <div class="bg-white">
        <img id="viewImg" alt="" class="w-full h-60 object-cover bg-gray-200">
      </div>
      <div class="px-5 py-4 space-y-2">
        <div class="flex items-start justify-between">
          <h3 id="viewTitle" class="text-xl font-semibold text-gray-900">Title</h3>
        </div>
        <p id="viewLocation" class="text-sm text-gray-600"></p>
        <div class="flex items-center gap-4 text-sm text-gray-700">
          <span id="viewPrice"></span>
          <span id="viewCapacity"></span>
        </div>
        <p id="viewDesc" class="text-sm text-gray-700 leading-6"></p>
        <div id="viewLocationLinkWrap" class="pt-2 hidden">
          <a id="viewLocationLink" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-gray-50 text-sm">
            📍 Open location
          </a>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-end gap-3">
          <button id="viewBookBtn" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700">Book Now</button>
          <button id="viewCloseBtn" class="px-4 py-2 rounded border hover:bg-gray-50">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking modal -->
  <div id="bookingModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-md rounded-xl shadow-lg overflow-hidden">
      <div class="px-5 py-3 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Book Room</h3>
        <button id="bookingClose" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>

      <form id="bookingForm" class="p-5 space-y-4">
        <input type="hidden" name="room_id" id="bookingRoomId">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
          <input id="bookingStart" name="start_date" type="date" required class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End date</label>
          <input id="bookingEnd" name="end_date" type="date" required class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Guests</label>
          <input id="bookingGuests" name="guests" type="number" min="1" value="1" class="w-full border rounded-lg px-3 py-2" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Extras / Requests</label>
          <div class="space-y-2">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="extras[]" value="extra_towel"> Extra towel</label><br>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="extras[]" value="extra_pillow"> Extra pillow</label><br>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="extras[]" value="early_checkin"> Early check-in</label><br>
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="extras[]" value="late_checkout"> Late check-out</label>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Additional notes</label>
          <textarea id="bookingNotes" name="notes" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" id="bookingCancel" class="px-4 py-2 rounded-lg border hover:bg-gray-50">Cancel</button>
          <button id="bookingSubmit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Confirm booking</button>
        </div>
        <div id="bookingError" class="text-sm text-red-600 hidden"></div>
      </form>
    </div>
  </div>

  <!-- Payment modal -->
  <div id="paymentModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-md rounded-xl shadow-lg overflow-hidden">
      <div class="px-5 py-3 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Upload Payment</h3>
        <button id="paymentClose" class="text-gray-500 hover:text-gray-700">✖</button>
      </div>
      <form id="paymentForm" class="p-5 space-y-4">
        <input type="hidden" id="paymentBookingId" name="booking_id">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
          <select id="paymentMethod" name="method" class="w-full border rounded-lg px-3 py-2">
            <option value="bank_transfer">Bank Transfer</option>
            <option value="gcash">GCash</option>
            <option value="paymaya">PayMaya</option>
            <option value="cod">COD</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
          <input id="paymentReference" name="reference" class="w-full border rounded-lg px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Receipt</label>
          <input id="paymentReceipt" name="receipt" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full" />
        </div>
        <p id="paymentError" class="text-sm text-red-600 hidden"></p>
        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" id="paymentCancel" class="px-4 py-2 rounded-lg border hover:bg-gray-50">Cancel</button>
          <button id="paymentSubmit" class="px-4 py-2 rounded-lg bg-blue-700 text-white hover:bg-blue-800">Submit</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Scripts -->
  <script src="assets/js/dashboard.js" defer></script>
</body>
</html>
