using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using InventoryApp.Models;
using InventoryApp.Services;

namespace InventoryApp.Pages
{
    public class IndexModel : PageModel
    {
        private readonly IInventoryService _inventoryService;

        public IndexModel(IInventoryService inventoryService)
        {
            _inventoryService = inventoryService;
        }

        public List<InventoryItem> Items { get; set; } = new();
        public decimal TotalValue { get; set; }
        public int LowStockCount { get; set; }
        public int OutOfStockCount { get; set; }

        [BindProperty]
        public InventoryItem NewItem { get; set; } = new();

        public void OnGet()
        {
            LoadData();
        }

        public IActionResult OnPostAddItem()
        {
            if (!ModelState.IsValid)
            {
                LoadData();
                return Page();
            }

            _inventoryService.AddItem(NewItem);
            return RedirectToPage();
        }

        public IActionResult OnPostRestock(int id, int amount)
        {
            _inventoryService.RestockItem(id, amount);
            return RedirectToPage();
        }

        public IActionResult OnPostDecrease(int id)
        {
            _inventoryService.DecreaseStock(id, 1);
            return RedirectToPage();
        }

        private void LoadData()
        {
            Items = _inventoryService.GetAllItems();
            TotalValue = _inventoryService.GetTotalInventoryValue();
            LowStockCount = _inventoryService.GetLowStockCount();
            OutOfStockCount = _inventoryService.GetOutOfStockCount();
        }
    }
}