<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>
   Cart Page
  </title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
   /* Custom font for the quantity buttons to replicate the style */
    .quantity-btn {
      font-family: monospace;
      font-size: 0.65rem;
      user-select: none;
    }
  </style>
 </head>
 <body class="bg-gray-100 min-h-screen flex flex-col">
  <header class="bg-[#4dc1c7] relative">
   <div class="absolute -top-10 -left-10 w-24 h-24 rounded-full bg-[#a1d7d9] opacity-50">
   </div>
   <div class="absolute -bottom-10 -right-10 w-24 h-24 rounded-full bg-[#a1d7d9] opacity-50">
   </div>
   <nav class="max-w-5xl mx-auto flex justify-end gap-8 p-4">
    <a aria-label="Home" class="text-black text-2xl" href="#">
     <i class="fas fa-home">
     </i>
    </a>
    <a aria-label="Cart" class="text-black text-2xl" href="#">
     <i class="fas fa-shopping-cart">
     </i>
    </a>
    <a aria-label="Notifications" class="text-black text-2xl" href="#">
     <i class="fas fa-bell">
     </i>
    </a>
    <a aria-label="User" class="text-black text-2xl" href="#">
     <i class="fas fa-user">
     </i>
    </a>
   </nav>
  </header>
  <main class="flex-grow max-w-5xl mx-auto p-6">
   <section class="bg-white p-6">
    <table class="w-full border-collapse text-[13px]">
     <thead>
      <tr class="border-b border-gray-300 text-left text-[13px]">
       <th class="py-2 pl-2 font-normal">
        Products
       </th>
       <th class="py-2 font-normal">
        Price
       </th>
       <th class="py-2 font-normal">
        Quantity
       </th>
       <th class="py-2 font-normal">
        Total
       </th>
      </tr>
     </thead>
     <tbody>
      <tr class="border-b border-gray-300">
       <td class="py-3 pl-2 flex items-center gap-2">
        <img alt="Brown binder with white label on a white background" class="flex-shrink-0" height="24" src="https://storage.googleapis.com/a1aa/image/c2899def-7ba9-4441-c321-3b5ec7bce49a.jpg" width="24"/>
        <span>
         Binder
        </span>
       </td>
       <td class="py-3 font-semibold">
        ₱ 85.00
       </td>
       <td class="py-3 text-[10px] text-gray-400 font-mono quantity-btn select-none">
        [-1+]
       </td>
       <td class="py-3 font-semibold">
        ₱ 85.00
       </td>
      </tr>
      <tr>
       <td class="py-3 pl-2 flex items-center gap-2">
        <img alt="Blue ballpen with black tip on a white background" class="flex-shrink-0" height="24" src="https://storage.googleapis.com/a1aa/image/75390f9c-21a7-4dcb-d5c4-5a23175673c7.jpg" width="24"/>
        <span>
         Ballpen
        </span>
       </td>
       <td class="py-3 font-semibold">
        ₱ 10.00
       </td>
       <td class="py-3 text-[10px] text-gray-400 font-mono quantity-btn select-none">
        [-2+]
       </td>
       <td class="py-3 font-semibold">
        ₱ 20.00
       </td>
      </tr>
     </tbody>
     <tfoot>
      <tr class="border-t border-gray-300 text-[13px] font-semibold">
       <td>
       </td>
       <td>
       </td>
       <td class="text-right pr-2">
        Subtotal
       </td>
       <td class="pr-2">
        ₱ 105.00
       </td>
      </tr>
      <tr>
       <td>
       </td>
       <td>
       </td>
       <td>
       </td>
       <td class="pr-2 pb-2">
        <button class="bg-blue-700 text-white text-[11px] font-semibold px-3 py-1 rounded" type="button">
         Checkout
        </button>
       </td>
      </tr>
     </tfoot>
    </table>
   </section>
  </main>
 </body>
</html>
