from pathlib import Path
p = Path(r'D:/ezzyrock/resources/views/partials/_body_sidebar.blade.php')
text = p.read_text(encoding='utf-8')
idx = text.index('user_plans.index_data')
pos = text.index("</svg>')", idx)
print('found', repr(text[pos:pos+20]))
new_text = text[:pos+10] + ';' + text[pos+10:]
p.write_text(new_text, encoding='utf-8')
print('patched')