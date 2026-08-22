import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import React from 'react';
import { useEditor } from '@/frontend/hooks/use-editor';

const ImageSettings = ({
   changeCustomValues,
}: {
   changeCustomValues: (e: React.ChangeEvent<HTMLInputElement>) => void;
}) => {
   const { editor } = useEditor();

   return (
      editor.editor.selectedElement.type === 'image' && (
         <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-2">
               <Label>Image Source (URL)</Label>
               <Input
                  id="src"
                  placeholder="https://domain.example.com/image.jpg"
                  onChange={changeCustomValues}
                  value={
                     typeof editor.editor.selectedElement.content ===
                        'object' &&
                     !Array.isArray(editor.editor.selectedElement.content) &&
                     editor.editor.selectedElement.content !== null &&
                     'src' in editor.editor.selectedElement.content
                        ? (editor.editor.selectedElement.content as any).src
                        : ''
                  }
               />
            </div>
            <div className="flex flex-col gap-2">
               <Label>Alternative Text</Label>
               <Input
                  id="alt"
                  placeholder="Describe the image"
                  onChange={changeCustomValues}
                  value={
                     typeof editor.editor.selectedElement.content ===
                        'object' &&
                     !Array.isArray(editor.editor.selectedElement.content) &&
                     editor.editor.selectedElement.content !== null &&
                     'alt' in editor.editor.selectedElement.content
                        ? (editor.editor.selectedElement.content as any).alt
                        : ''
                  }
               />
            </div>
         </div>
      )
   );
};

export default ImageSettings;
