import os
import re

dir_path = r'c:\xampp\htdocs\IMS\v2'

replacements = [
    # Alerts
    (r'class="bg-red-50 border border-red-200 text-red-700[^"]*"', r'class="alert alert-danger"'),
    (r'class="bg-green-50 border border-green-200 text-green-700[^"]*"', r'class="alert alert-success"'),
    (r'class="bg-yellow-50 border border-yellow-200 text-yellow-700[^"]*"', r'class="alert alert-warning"'),
    
    # Cards
    (r'class="(?:bg-white|bg-card) [a-zA-Z0-*-_\s]*(?:shadow|overflow-hidden)[^"]*"', r'class="card"'),
    (r'class="(?:px-6 py-4|p-6 border-b) [a-zA-Z0-*-_\s]*(?:bg-gradient|bg-gray|border-b)[^"]*"', r'class="card-header"'),
    (r'class="p-6"', r'class="card-body"'),
    (r'class="px-6 py-4 border-t[^"]*"', r'class="card-footer"'),
    
    # Forms
    (r'class="(?:w-full )?px-4 py-2\.5 border border-gray-300 rounded-lg[^"]*"', r'class="form-control"'),
    (r'class="block text-sm font-medium text-gray-700 mb-2"', r'class="form-label"'),
    
    # Buttons
    (r'class="[^"]*(?:bg-[a-z]+-600|bg-primary-[0-9]+)[^"]*text-white[^"]*"', r'class="btn btn-primary"'),
    (r'class="[^"]*bg-gray-100 hover:bg-gray-200[^"]*"', r'class="btn btn-outline"'),
    (r'class="[^"]*bg-red-100 hover:bg-red-200 text-red-700[^"]*"', r'class="btn btn-sm btn-danger"'),
    
    # Tables
    (r'<table class="w-full text-left border-collapse">', r'<table class="table">'),
    (r'class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"', r''),
    (r'class="hover:bg-gray-50[a-zA-Z\s-]*"', r''),
    (r'<td class="py-3 px-4 border-b border-gray-100">', r'<td>'),
    (r'<th class="py-3 px-4 border-b border-gray-100">', r'<th>'),
    
    # Layout and Utils
    (r'class="max-w-[\w]+"', r'class="mb-4"'),
    (r'class="grid grid-cols-[1-4](?: md:grid-cols-[1-4])? gap-[0-9]+"', r'class="d-flex flex-wrap gap-4"'),
    (r'class="flex items-center gap-[0-9]+"([^>]*>)', r'class="d-flex align-center gap-3"\1'),
    (r'class="space-y-[0-9]+"', r'class="d-flex flex-column gap-3"')
]

def map_select_to_form_select(content):
    # Form-control on selects should be form-select
    return re.sub(r'<select([^>]*)class="form-control"', r'<select\1class="form-select"', content)

files_changed = 0

for filename in os.listdir(dir_path):
    if filename.endswith(".php") and filename not in ['design-system.css', 'receipt.php']:
        file_path = os.path.join(dir_path, filename)
        with open(file_path, 'r', encoding='utf-8') as file:
            content = file.read()
        
        orig_content = content
        
        for p, r in replacements:
            content = re.sub(p, r, content)
            
        content = map_select_to_form_select(content)
        
        # Additional table cleanup specifically for table headers and rows with tailwind
        content = re.sub(r'<th class="[^"]*text-gray-500[^"]*">', r'<th>', content)
        content = re.sub(r'<td class="[^"]*p-4[^"]*">', r'<td>', content)
        content = re.sub(r'class="px-[0-9]+ py-[0-9]+[^"]*"', r'', content)
        
        if content != orig_content:
            with open(file_path, 'w', encoding='utf-8') as file:
                file.write(content)
            files_changed += 1
            print(f"Updated {filename}")

print(f"Total files updated: {files_changed}")
